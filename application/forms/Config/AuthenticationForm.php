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

namespace Icinga\Form\Config;

use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Application\Icinga;
use \Icinga\Application\Logger;
use \Icinga\Application\DbAdapterFactory;

use \Icinga\Web\Form;
use \Icinga\Web\Form\Element\Note;
use \Icinga\Web\Form\Decorator\ConditionalHidden;
use \Zend_Config;
use \Zend_Form_Element_Text;
use \Zend_Form_Element_Select;

class AuthenticationForm extends Form
{
    /**
     * The configuration to use for populating this form
     *
     * @var IcingaConfig
     */
    private $config = null;

    /**
     * The resources to use instead of the factory provided ones (use for testing)
     *
     * @var null
     */
    private $resources = null;

    /**
     * Set an alternative array of resources that should be used instead of the DBFactory resource set
     * (used for testing)
     *
     * @param array $resources              The resources to use for populating the db selection field
     */
    public function setResources(array $resources)
    {
        $this->resources = $resources;
    }

    /**
     * Return content of the resources.ini or previously set resources for displaying in the database selection field
     *
     * @return array
     */
    public function getResources()
    {
        if ($this->resources === null ) {
            return DbAdapterFactory::getResources();
        } else {
            return $this->resources;
        }
    }

    /**
     * Set the configuration to be used for this form
     *
     * @param IcingaConfig $cfg
     */
    public function setConfiguration($cfg)
    {
        $this->config = $cfg;
    }

    private function addProviderFormForDb($name, $backend)
    {

        $backends = array();
        foreach ($this->getResources() as $resname => $resource)
        {
            if ($resource['type'] !== 'db') {
                continue;
            }
            $backends[$resname] = $resname;
        }

        $this->addElement(
            'select',
            'backend_' . $name . '_resource',
            array(
                'label'         =>  'Database connection',
                'required'      =>  true,
                'value'         =>  $backend->get('resource'),
                'multiOptions'  =>  $backends
            )
        );


        $this->addElement(
            'submit',
            'backend_' . $name . '_remove',
            array(
                'label'     => 'Remove this backend',
                'required'  => true
            )
        );

        $this->addDisplayGroup(
            array(
                'backend_' . $name . '_resource',
                'backend_' . $name . '_remove'
            ),
            'auth_provider_' . $name,
            array(
                'legend' => 'DB Authentication ' . $name
            )
        );
    }

    private function addProviderFormForLdap($name, $backend)
    {
        $this->addElement(
            'text',
            'backend_' . $name . '_hostname',
            array(
                'label'     => 'LDAP server host',
                'value'     => $backend->get('hostname', 'localhost'),
                'required'  => true
            )
        );

        $this->addElement(
            'text',
            'backend_' . $name . '_root_dn',
            array(
                'label'     => 'LDAP root dn',
                'value'     => $backend->get('hostname', 'ou=people,dc=icinga,dc=org'),
                'required'  => true
            )
        );

        $this->addElement(
            'text',
            'backend_' . $name . '_bind_dn',
            array(
                'label'     => 'LDAP bind dn',
                'value'     => $backend->get('bind_dn', 'cn=admin,cn=config'),
                'required'  => true
            )
        );

        $this->addElement(
            'password',
            'backend_' . $name . '_bind_pw',
            array(
                'label'     =>  'LDAP bind password',
                'value'     =>  $backend->get('bind_pw', 'admin'),
                'required'  => true
            )
        );

        $this->addElement(
            'text',
            'backend_' . $name . '_bind_user_class',
            array(
                'label'     => 'LDAP user object class',
                'value'     => $backend->get('user_class', 'inetOrgPerson'),
                'required'  => true
            )
        );

        $this->addElement(
            'text',
            'backend_' . $name . '_bind_user_name_attribute',
            array(
                'label'     => 'LDAP user name attribute',
                'value'     => $backend->get('user_name_attribute', 'uid'),
                'required'  => true
            )
        );

        $this->addElement(
            'submit',
            'backend_' . $name . '_remove',
            array(
                'label' => 'Remove this backend'
            )
        );

        $this->addDisplayGroup(
            array(
                'backend_' . $name . '_hostname',
                'backend_' . $name . '_root_dn',
                'backend_' . $name . '_bind_dn',
                'backend_' . $name . '_bind_pw',
                'backend_' . $name . '_bind_user_class',
                'backend_' . $name . '_bind_user_name_attribute',
                'backend_' . $name . '_remove'
            ),
            'auth_provider_' . $name,
            array(
                'legend' => 'LDAP Authentication ' . $name
            )
        );
    }


    public function addPriorityButtons($name, $pos)
    {
        if ($pos > 0) {
            $this->addElement(
                'submit',
                'priority_change_'.$name.'_down',
                array(
                    'label' => 'Move up in authentication order',
                    'value' => $pos-1
                )
            );
        }
        if ($pos+1 < count($this->config->keys())) {
            $this->addElement(
                'submit',
                'priority_change_'.$name.'_up',
                array(
                    'label' => 'Move down in authentication order',
                    'value' => $pos+1
                )
            );
        }
    }

    public function create()
    {
        $this->addElement(
            'submit',
            'add_backend',
            array(
                'label' => 'Add a new authentication provider',
                'class' => 'btn'
            )
        );
        $pos = 0;
        foreach ($this->config as $name => $backend) {

            $type = strtolower($backend->get('backend'));
            if ($type === 'db') {
                $this->addProviderFormForDb($name, $backend);
            } elseif ($type === 'ldap') {
                $this->addProviderFormForLdap($name, $backend);
            } else {
                Logger::error('Unsupported backend found in authentication configuration: ' . $backend->get('backend'));
                continue;
            }
            $this->addPriorityButtons($name, $pos);

            $pos++;
        }
        $this->setSubmitLabel('Save changes');
    }
}