<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Config;

use Exception;
use InvalidArgumentException;
use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\ConfigForm;
use Icinga\Web\Form;

/**
 * Form for managing monitoring backends
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
     * The backend to load when displaying the form for the first time
     *
     * @var string
     */
    protected $backendToLoad;

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
            if ($resource->type === 'db') {
                $resources['ido'][$name] = $name;
            }
        }

        if (empty($resources)) {
            throw new ConfigurationError($this->translate(
                'Could not find any valid monitoring backend resources. Please configure a database resource first.'
            ));
        }

        $this->resources = $resources;
        return $this;
    }

    /**
     * Populate the form with the given backend's config
     *
     * @param   string  $name
     *
     * @return  $this
     *
     * @throws  NotFoundError   In case no backend with the given name is found
     */
    public function load($name)
    {
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError('No monitoring backend called "%s" found', $name);
        }

        $this->backendToLoad = $name;
        return $this;
    }

    /**
     * Add a new monitoring backend
     *
     * The backend to add is identified by the array-key `name'.
     *
     * @param   array   $data
     *
     * @return  $this
     *
     * @throws  InvalidArgumentException    In case $data does not contain a backend name
     * @throws  IcingaException             In case a backend with the same name already exists
     */
    public function add(array $data)
    {
        if (! isset($data['name'])) {
            throw new InvalidArgumentException('Key \'name\' missing');
        }

        $backendName = $data['name'];
        if ($this->config->hasSection($backendName)) {
            throw new IcingaException('A monitoring backend with the name "%s" does already exist', $backendName);
        }

        unset($data['name']);
        $this->config->setSection($backendName, $data);
        return $this;
    }

    /**
     * Edit a monitoring backend
     *
     * @param   string  $name
     * @param   array   $data
     *
     * @return  $this
     *
     * @throws  NotFoundError   In case no backend with the given name is found
     */
    public function edit($name, array $data)
    {
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError('No monitoring backend called "%s" found', $name);
        }

        $backendConfig = $this->config->getSection($name);
        if (isset($data['name'])) {
            if ($data['name'] !== $name) {
                $this->config->removeSection($name);
                $name = $data['name'];
            }

            unset($data['name']);
        }

        $backendConfig->merge($data);
        foreach ($backendConfig->toArray() as $k => $v) {
            if ($v === null) {
                unset($backendConfig->$k);
            }
        }

        $this->config->setSection($name, $backendConfig);
        return $this;
    }

    /**
     * Remove a monitoring backend
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function delete($name)
    {
        $this->config->removeSection($name);
        return $this;
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData
     */
    public function createElements(array $formData)
    {
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
                'description'   => $this->translate(
                    'The name of this monitoring backend that is used to differentiate it from others'
                ),
                'validators'    => array(
                    array(
                        'Regex',
                        false,
                        array(
                            'pattern'  => '/^[^\\[\\]:]+$/',
                            'messages' => array(
                                'regexNotMatch' => $this->translate(
                                    'The name cannot contain \'[\', \']\' or \':\'.'
                                )
                            )
                        )
                    )
                )
            )
        );

        $resourceType = isset($formData['type']) ? $formData['type'] : null;

        $resourceTypes = array();
        if ($resourceType === 'ido' || array_key_exists('ido', $this->resources)) {
            $resourceTypes['ido'] = 'IDO Backend';
        }

        if ($resourceType === null) {
            $resourceType = key($resourceTypes);
        } elseif ($resourceType === 'livestatus') {
            throw new ConfigurationError(
                'We\'ve disabled livestatus support for now because it\'s not feature complete yet'
            );
        }

        $this->addElement(
            'select',
            'type',
            array(
                'required'      => true,
                'autosubmit'    => true,
                'label'         => $this->translate('Backend Type'),
                'description'   => $this->translate(
                    'The type of data source used for retrieving monitoring information'
                ),
                'multiOptions'  => $resourceTypes
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

    /**
     * Populate the configuration of the backend to load
     */
    public function onRequest()
    {
        if ($this->backendToLoad) {
            $data = $this->config->getSection($this->backendToLoad)->toArray();
            $data['name'] = $this->backendToLoad;
            $this->populate($data);
        }
    }

    /**
     * Validate the ido instance schema resource
     *
     * @param   Form            $form
     * @param   ConfigObject    $resourceConfig
     *
     * @return  bool                                Whether validation succeeded or not
     */
    public static function isValidIdoSchema(Form $form, ConfigObject $resourceConfig)
    {
        try {
            $resource = ResourceFactory::createResource($resourceConfig);
            $result = $resource->select()->from('icinga_dbversion', array('version'));
            $result->fetchOne();
        } catch (Exception $e) {
            $form->addError(
                $form->translate(
                    'IDO schema validation failed, it looks like that the IDO schema is missing in the given database.'
                )
            );
            return false;
        }
        return true;
    }

    /**
     * Validate the ido instance availability
     *
     * @param   Form            $form
     * @param   ConfigObject    $resourceConfig
     *
     * @return  bool                                Whether validation succeeded or not
     */
    public static function isValidIdoInstance(Form $form, ConfigObject $resourceConfig)
    {
        $resource = ResourceFactory::createResource($resourceConfig);
        $result = $resource->select()->from('icinga_instances', array('instance_name'));
        $instances = $result->fetchAll();

        if (count($instances) === 1) {
            return true;
        } elseif (count($instances) > 1) {
            $form->warning(
                $form->translate(
                    'IDO instance validation failed, because there are multiple instances available.'
                )
            );
            return false;
        }

        $form->error(
            $form->translate(
                'IDO instance validation failed, because there is no IDO instance available.'
            )
        );

        return false;
    }
}
