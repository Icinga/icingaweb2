<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Config\Transport;

use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Form;

class RemoteTransportForm extends Form
{
    /**
     * The available resources split by type
     *
     * @var array
     */
    protected $resources;

    /**
     * (non-PHPDoc)
     * @see Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setName('form_config_command_transport_remote');
    }

    /**
     * Load all available ssh identity resources
     *
     * @return $this
     *
     * @throws \Icinga\Exception\ConfigurationError
     */
    public function loadResources()
    {
        $resourceConfig = ResourceFactory::getResourceConfigs();

        $resources = array();
        foreach ($resourceConfig as $name => $resource) {
            if ($resource->type === 'ssh') {
                $resources['ssh'][$name] = $name;
            }
        }

        if (empty($resources)) {
            throw new ConfigurationError($this->translate('Could not find any valid SSH resources'));
        }

        $this->resources = $resources;

        return $this;
    }

    /**
     * Check whether ssh identity resources exists or not
     *
     * @return boolean
     */
    public function hasResources()
    {
        $resourceConfig = ResourceFactory::getResourceConfigs();

        foreach ($resourceConfig as $name => $resource) {
            if ($resource->type === 'ssh') {
                return true;
            }
        }
        return false;
    }

    /**
     * (non-PHPDoc)
     * @see Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $useResource = false;

        if ($this->hasResources()) {
            $useResource = isset($formData['use_resource'])
                ? $formData['use_resource'] : $this->getValue('use_resource');

            $this->addElement(
                'checkbox',
                'use_resource',
                array(
                    'label'         => $this->translate('Use SSH Identity'),
                    'description'   => $this->translate('Make use of the ssh identity resource'),
                    'autosubmit'    => true,
                    'ignore'        => true
                )
            );
        }

        if ($useResource) {

            $this->loadResources();

            $decorators = static::$defaultElementDecorators;
            array_pop($decorators); // Removes the HtmlTag decorator

            $this->addElement(
                'select',
                'resource',
                array(
                    'required'      => true,
                    'label'         => $this->translate('SSH Identity'),
                    'description'   => $this->translate('The resource to use'),
                    'decorators'    => $decorators,
                    'multiOptions'  => $this->resources['ssh'],
                    'value'         => current($this->resources['ssh']),
                    'autosubmit'    => false
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
        }

        $this->addElements(array(
            array(
                'text',
                'host',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Host'),
                    'description'   => $this->translate(
                        'Hostname or address of the remote Icinga instance'
                    )
                )
            ),
            array(
                'number',
                'port',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Port'),
                    'description'   => $this->translate('SSH port to connect to on the remote Icinga instance'),
                    'value'         => 22
                )
            )
        ));

        if (! $useResource) {
            $this->addElement(
                'text',
                'user',
                array(
                    'required'      => true,
                    'label'         => $this->translate('User'),
                    'description'   => $this->translate(
                        'User to log in as on the remote Icinga instance. Please note that key-based SSH login must be'
                        . ' possible for this user'
                    )
                )
            );
        }

        $this->addElement(
            'text',
            'path',
            array(
                'required'      => true,
                'label'         => $this->translate('Command File'),
                'value'         => '/var/run/icinga2/cmd/icinga2.cmd',
                'description'   => $this->translate('Path to the Icinga command file on the remote Icinga instance')
            )
        );

        return $this;
    }
}
