<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Forms\Config;

use InvalidArgumentException;
use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use Icinga\Forms\ConfigForm;
use Icinga\Web\Notification;

/**
 * Form class for creating/modifying monitoring backends
 */
class BackendConfigForm extends ConfigForm
{
    /**
     * The available monitoring backend resources split by type
     *
     * @var array
     */
    protected $resources;

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_monitoring_backends');
        $this->setSubmitLabel(mt('monitoring', 'Save Changes'));
    }

    /**
     * Set the resource configuration to use
     *
     * @param   Config      $resources      The resource configuration
     *
     * @return  $this
     *
     * @throws  ConfigurationError          In case there are no valid monitoring backend resources
     */
    public function setResourceConfig(Config $resourceConfig)
    {
        $resources = array();
        foreach ($resourceConfig as $name => $resource) {
            if ($resource->type === 'db' || $resource->type === 'livestatus') {
                $resources[$resource->type === 'db' ? 'ido' : 'livestatus'][$name] = $name;
            }
        }

        if (empty($resources)) {
            throw new ConfigurationError(mt('monitoring', 'Could not find any valid monitoring backend resources'));
        }

        $this->resources = $resources;
        return $this;
    }

    /**
     * Add a particular monitoring backend
     *
     * The backend to add is identified by the array-key `name'.
     *
     * @param   array   $values             The values to extend the configuration with
     *
     * @return  $this
     *
     * @throws  InvalidArgumentException    In case the backend does already exist
     */
    public function add(array $values)
    {
        $name = isset($values['name']) ? $values['name'] : '';
        if (! $name) {
            throw new InvalidArgumentException(mt('monitoring', 'Monitoring backend name missing'));
        } elseif ($this->config->hasSection($name)) {
            throw new InvalidArgumentException(mt('monitoring', 'Monitoring backend already exists'));
        }

        unset($values['name']);
        $this->config->setSection($name, $values);
        return $this;
    }

    /**
     * Edit a particular monitoring backend
     *
     * @param   string  $name               The name of the backend to edit
     * @param   array   $values             The values to edit the configuration with
     *
     * @return  array                       The edited backend configuration
     *
     * @throws  InvalidArgumentException    In case the backend does not exist
     */
    public function edit($name, array $values)
    {
        if (! $name) {
            throw new InvalidArgumentException(mt('monitoring', 'Old monitoring backend name missing'));
        } elseif (! ($newName = isset($values['name']) ? $values['name'] : '')) {
            throw new InvalidArgumentException(mt('monitoring', 'New monitoring backend name missing'));
        } elseif (! $this->config->hasSection($name)) {
            throw new InvalidArgumentException(mt('monitoring', 'Unknown monitoring backend provided'));
        }

        unset($values['name']);
        $this->config->setSection($name, $values);
        return $this->config->getSection($name);
    }

    /**
     * Remove the given monitoring backend
     *
     * @param   string      $name           The name of the backend to remove
     *
     * @return  array                       The removed backend configuration
     *
     * @throws  InvalidArgumentException    In case the backend does not exist
     */
    public function remove($name)
    {
        if (! $name) {
            throw new InvalidArgumentException(mt('monitoring', 'Monitoring backend name missing'));
        } elseif (! $this->config->hasSection($name)) {
            throw new InvalidArgumentException(mt('monitoring', 'Unknown monitoring backend provided'));
        }

        $backendConfig = $this->config->getSection($name);
        $this->config->removeSection($name);
        return $backendConfig;
    }

    /**
     * Add or edit a monitoring backend and save the configuration
     *
     * @see Form::onSuccess()
     */
    public function onSuccess()
    {
        $monitoringBackend = $this->request->getQuery('backend');
        try {
            if ($monitoringBackend === null) { // create new backend
                $this->add($this->getValues());
                $message = mt('monitoring', 'Monitoring backend "%s" has been successfully created');
            } else { // edit existing backend
                $this->edit($monitoringBackend, $this->getValues());
                $message = mt('monitoring', 'Monitoring backend "%s" has been successfully changed');
            }
        } catch (InvalidArgumentException $e) {
            Notification::error($e->getMessage());
            return;
        }

        if ($this->save()) {
            Notification::success(sprintf($message, $this->getElement('name')->getValue()));
        } else {
            return false;
        }
    }

    /**
     * Populate the form in case a monitoring backend is being edited
     *
     * @see Form::onRequest()
     *
     * @throws  ConfigurationError      In case the backend name is missing in the request or is invalid
     */
    public function onRequest()
    {
        $monitoringBackend = $this->request->getQuery('backend');
        if ($monitoringBackend !== null) {
            if ($monitoringBackend === '') {
                throw new ConfigurationError(mt('monitoring', 'Monitoring backend name missing'));
            } elseif (! $this->config->hasSection($monitoringBackend)) {
                throw new ConfigurationError(mt('monitoring', 'Unknown monitoring backend provided'));
            }

            $backendConfig = $this->config->getSection($monitoringBackend)->toArray();
            $backendConfig['name'] = $monitoringBackend;
            $this->populate($backendConfig);
        }
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $resourceType = isset($formData['type']) ? $formData['type'] : key($this->resources);

        $resourceTypes = array();
        if ($resourceType === 'ido' || array_key_exists('ido', $this->resources)) {
            $resourceTypes['ido'] = 'IDO Backend';
        }
        if ($resourceType === 'livestatus' || array_key_exists('livestatus', $this->resources)) {
            $resourceTypes['livestatus'] = 'Livestatus';
        }

        $this->addElement(
            'checkbox',
            'disabled',
            array(
                'required'  => true,
                'label'     => mt('monitoring', 'Disable This Backend')
            )
        );
        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => mt('monitoring', 'Backend Name'),
                'description'   => mt('monitoring', 'The identifier of this backend')
            )
        );
        $this->addElement(
            'select',
            'type',
            array(
                'required'      => true,
                'autosubmit'    => true,
                'label'         => mt('monitoring', 'Backend Type'),
                'description'   => mt('monitoring', 'The data source used for retrieving monitoring information'),
                'multiOptions'  => $resourceTypes,
                'value'         => $resourceType
            )
        );

        $resourceElement = $this->createElement(
            'select',
            'resource',
            array(
                'required'      => true,
                'label'         => mt('monitoring', 'Resource'),
                'description'   => mt('monitoring', 'The resource to use'),
                'multiOptions'  => $this->resources[$resourceType],
                'autosubmit'    => true
            )
        );

        if (empty($formData)) {
            $options = $resourceElement->options;
            $resourceName = array_shift($options);
        } else {
            $resourceName = (isset($formData['resource'])) ? $formData['resource'] : $this->getValue('resource');
        }

        $this->addElement($resourceElement);

        if ($resourceElement) {
            $this->addElement(
                'note',
                'resource_note',
                array(
                    'value' => sprintf(
                        '<a href="%s" data-base-target="_main">%s</a>',
                        $this->getView()->url('config/editresource', array('resource' => $resourceName)),
                        mt('monitoring', 'Show resource configuration')
                    ),
                    'escape' => false
                )
            );
        }
    }
}
