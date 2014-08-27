<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config\Instance;

use \Zend_Config;
use \Icinga\Web\Form;

/**
 * Form for editing existing instances
 */
class EditInstanceForm extends Form
{
    /**
     * The instance to edit
     *
     * @var Zend_Config
     */
    private $instance;

    /**
     * The type of the instance
     *
     * 'local' when no host is given, otherwise 'remote'
     *
     * @var string
     */
    private $instanceType = 'local';

    /**
     * Set instance configuration to be used for initial form population
     *
     * @param Zend_Config $config
     */
    public function setInstanceConfiguration($config)
    {
        $this->instance = $config;
        if (isset($this->instance->host)) {
            $this->instanceType = 'remote';
        }
    }

    /**
     * Add a form field for selecting the command pipe type (local or remote)
     */
    private function addTypeSelection()
    {
        $this->addElement(
            'select',
            'instance_type',
            array(
                'value' => $this->instanceType,
                'multiOptions' => array(
                    'local'     => 'Local Command Pipe',
                    'remote'    => 'Remote Command Pipe'
                )
            )
        );
        $this->enableAutoSubmit(array('instance_type'));
    }

    /**
     * Add form elements for remote instance
     */
    private function addRemoteInstanceForm()
    {
        $this->addNote('When configuring a remote host, you need to setup passwordless key authentication');

        $this->addElement(
            'text',
            'instance_remote_host',
            array(
                'label'     =>  'Remote Host',
                'required'  =>  true,
                'value'     =>  $this->instance->host,
                'helptext'  =>  'Enter the hostname or address of the machine on which the icinga instance is running'
            )
        );

        $this->addElement(
            'text',
            'instance_remote_port',
            array(
                'label'     =>  'Remote SSH Port',
                'required'  =>  true,
                'value'     =>  $this->instance->get('port', 22),
                'helptext'  =>  'Enter the ssh port to use for connecting to the remote icigna instance'
            )
        );

        $this->addElement(
            'text',
            'instance_remote_user',
            array(
                'label'         =>  'Remote SSH User',
                'value'         =>  $this->instance->user,
                'helptext'      =>  'Enter the username to use for connecting '
                                    . 'to the remote machine or leave blank for default'
            )
        );
    }

    /**
     * Create this form
     *
     * @see Icinga\Web\Form::create
     */
    public function create()
    {
        $this->addTypeSelection();
        if ($this->getRequest()->getParam('instance_type', $this->instanceType) === 'remote') {
            $this->addRemoteInstanceForm();
        }
        $this->addElement(
            'text',
            'instance_path',
            array(
                'label'     =>  'Remote Pipe Filepath',
                'required'  =>  true,
                'value'     =>  $this->instance->get('path', '/usr/local/icinga/var/rw/icinga.cmd'),
                'helptext'  =>  'The file path where the icinga commandpipe can be found'
            )
        );
        $this->setSubmitLabel('{{SAVE_ICON}} Save');
    }

    /**
     * Return the configuration set by this form
     *
     * @return Zend_Config The configuration set in this form
     */
    public function getConfig()
    {
        $values = $this->getValues();
        $config =  array(
            'path'  => $values['instance_path']
        );
        if ($values['instance_type'] === 'remote') {
            $config['host'] = $values['instance_remote_host'];
            $config['port'] = $values['instance_remote_port'];
            if (isset($values['instance_remote_user']) && $values['instance_remote_user'] != '') {
                $config['user'] = $values['instance_remote_user'];
            }
        }
        return new Zend_Config($config);
    }
}
