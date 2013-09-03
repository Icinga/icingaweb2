<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
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
