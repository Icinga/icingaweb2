<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    /**
     * Set the resource configuration to use
     *
     * @param   Config  $resourceConfig     The resource configuration
     *
     * @return  $this
     *
     * @throws  ConfigurationError          In case there are no valid monitoring backend resources
     */
    public function setResourceConfig(Config $resourceConfig)
    {
        $resources = array();
        foreach ($resourceConfig as $name => $resource) {
//            if ($resource->type === 'db' || $resource->type === 'livestatus') {
//                $resources[$resource->type === 'db' ? 'ido' : 'livestatus'][$name] = $name;
//            }
            if ($resource->type === 'db') {
                $resources['ido'][$name] = $name;
            }
        }

        if (empty($resources)) {
            throw new ConfigurationError($this->translate('Could not find any valid monitoring backend resources'));
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
            throw new InvalidArgumentException($this->translate('Monitoring backend name missing'));
        } elseif ($this->config->hasSection($name)) {
            throw new InvalidArgumentException($this->translate('Monitoring backend already exists'));
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
            throw new InvalidArgumentException($this->translate('Old monitoring backend name missing'));
        } elseif (! ($newName = isset($values['name']) ? $values['name'] : '')) {
            throw new InvalidArgumentException($this->translate('New monitoring backend name missing'));
        } elseif (! $this->config->hasSection($name)) {
            throw new InvalidArgumentException($this->translate('Unknown monitoring backend provided'));
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
            throw new InvalidArgumentException($this->translate('Monitoring backend name missing'));
        } elseif (! $this->config->hasSection($name)) {
            throw new InvalidArgumentException($this->translate('Unknown monitoring backend provided'));
        }

        $backendConfig = $this->config->getSection($name);
        $this->config->removeSection($name);
        return $backendConfig;
    }

    /**
     * Add or edit a monitoring backend and save the configuration
     */
    public function onSuccess()
    {
        $monitoringBackend = $this->request->getQuery('backend');
        try {
            if ($monitoringBackend === null) { // Create new backend
                $this->add($this->getValues());
                $message = $this->translate('Monitoring backend "%s" has been successfully created');
            } else { // Edit existing backend
                $this->edit($monitoringBackend, $this->getValues());
                $message = $this->translate('Monitoring backend "%s" has been successfully changed');
            }
        } catch (InvalidArgumentException $e) {
            Notification::error($e->getMessage());
            return null;
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
     * @throws  ConfigurationError  In case the backend name is missing in the request or is invalid
     */
    public function onRequest()
    {
        $monitoringBackend = $this->request->getQuery('backend');
        if ($monitoringBackend !== null) {
            if ($monitoringBackend === '') {
                throw new ConfigurationError($this->translate('Monitoring backend name missing'));
            } elseif (! $this->config->hasSection($monitoringBackend)) {
                throw new ConfigurationError($this->translate('Unknown monitoring backend provided'));
            }

            $backendConfig = $this->config->getSection($monitoringBackend)->toArray();
            $backendConfig['name'] = $monitoringBackend;
            $this->populate($backendConfig);
        }
    }

    /**
     * (non-PHPDoc)
     * @see Form::createElements() For the method documentation.
     */
    public function createElements(array $formData)
    {
        $resourceType = isset($formData['type']) ? $formData['type'] : key($this->resources);

        if ($resourceType === 'livestatus') {
            throw new ConfigurationError(
                'We\'ve disabled livestatus support for now because it\'s not feature complete yet'
            );
        }

        $resourceTypes = array();
        if ($resourceType === 'ido' || array_key_exists('ido', $this->resources)) {
            $resourceTypes['ido'] = 'IDO Backend';
        }
//        if ($resourceType === 'livestatus' || array_key_exists('livestatus', $this->resources)) {
//            $resourceTypes['livestatus'] = 'Livestatus';
//        }

        $this->addElement(
            'checkbox',
            'disabled',
            array(
                'required'  => true,
                'label'     => $this->translate('Disable This Backend')
            )
        );
        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Backend Name'),
                'description'   => $this->translate('The identifier of this backend')
            )
        );
        $this->addElement(
            'select',
            'type',
            array(
                'required'      => true,
                'autosubmit'    => true,
                'label'         => $this->translate('Backend Type'),
                'description'   => $this->translate('The data source used for retrieving monitoring information'),
                'multiOptions'  => $resourceTypes,
                'value'         => $resourceType
            )
        );

        $decorators = static::$defaultElementDecorators;
        array_pop($decorators); // Removes the HtmlTag decorator
        $this->addElement(
            'select',
            'resource',
            array(
                'required'      => true,
                'label'         => $this->translate('Resource'),
                'description'   => $this->translate('The resource to use'),
                'multiOptions'  => $this->resources[$resourceType],
                'value'         => current($this->resources[$resourceType]),
                'decorators'    => $decorators,
                'autosubmit'    => true
            )
        );
        $resourceName = isset($formData['resource']) ? $formData['resource'] : $this->getValue('resource');
        $this->addElement(
            'note',
            'resource_note',
            array(
                'escape'        => false,
                'decorators'    => $decorators,
                'value'         => sprintf(
                    '<a href="%1$s" data-base-target="_next" title="%2$s" aria-label="%2$s">%3$s</a>',
                    $this->getView()->url('config/editresource', array('resource' => $resourceName)),
                    sprintf($this->translate('Show the configuration of the %s resource'), $resourceName),
                    $this->translate('Show resource configuration')
                )
            )
        );
        $this->addDisplayGroup(
            array('resource', 'resource_note'),
            'resource-group',
            array(
                'decorators'    => array(
                    'FormElements',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'element'))
                )
            )
        );
    }
}
