<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Authentication;

use \Icinga\Authentication\Backend\LdapUserBackend;
use \Exception;
use \Zend_Config;
use \Icinga\Web\Form;

/**
 * Form for adding or modifying LDAP authentication backends
 */
class LdapBackendForm extends BaseBackendForm
{
    /**
     * Create this form and add all required elements
     *
     * @see Form::create()
     */
    public function create()
    {
        $this->setName('form_modify_backend');
        $name = $this->filterName($this->getBackendName());
        $backend = $this->getBackend();

        $this->addElement(
            'text',
            'backend_'.$name.'_name',
            array(
                'required'      => true,
                'allowEmpty'    =>  false,
                'label'         => 'Backend Name',
                'helptext'      => 'The name of this authentication backend',
                'value'         => $this->getBackendName()
            )
        );

        $this->addElement(
            'text',
            'backend_' . $name . '_hostname',
            array(
                'label'         => 'LDAP Server Host',
                'allowEmpty'    =>  false,
                'value'         => $backend->get('hostname', 'localhost'),
                'helptext'      => 'The hostname or address of the LDAP server to use for authentication',
                'required'      => true
            )
        );

        $this->addElement(
            'text',
            'backend_' . $name . '_root_dn',
            array(
                'label'     => 'LDAP Root DN',
                'value'     => $backend->get('root_dn', 'ou=people,dc=icinga,dc=org'),
                'helptext'  => 'The path where users can be found on the ldap server',
                'required'  => true
            )
        );

        $this->addElement(
            'text',
            'backend_' . $name . '_bind_dn',
            array(
                'label'     => 'LDAP Bind DN',
                'value'     => $backend->get('bind_dn', 'cn=admin,cn=config'),
                'helptext'  => 'The user dn to use for querying the ldap server',
                'required'  => true
            )
        );

        $this->addElement(
            'password',
            'backend_' . $name . '_bind_pw',
            array(
                'label'             => 'LDAP Bind Password',
                'renderPassword'    => true,
                'value'             => $backend->get('bind_pw', 'admin'),
                'helptext'          => 'The password to use for querying the ldap server',
                'required'          => true
            )
        );

        $this->addElement(
            'text',
            'backend_' . $name . '_user_class',
            array(
                'label'     => 'LDAP User Object Class',
                'value'     => $backend->get('user_class', 'inetOrgPerson'),
                'helptext'  => 'The object class used for storing users on the ldap server',
                'required'  => true
            )
        );

        $this->addElement(
            'text',
            'backend_' . $name . '_user_name_attribute',
            array(
                'label'     => 'LDAP User Name Attribute',
                'value'     => $backend->get('user_name_attribute', 'uid'),
                'helptext'  => 'The attribute name used for storing the user name on the ldap server',
                'required'  => true
            )
        );

        $this->addElement(
            'button',
            'btn_submit',
            array(
                'type'      => 'submit',
                'value'     => '1',
                'escape'    => false,
                'class'     => 'btn btn-cta btn-wide',
                'label'     => '<i class="icinga-icon-save"></i> Save Backend'
            )
        );
    }

    /**
     * Return the ldap authentication backend configuration for this form
     *
     * @return  array
     *
     * @see     BaseBackendForm::getConfig()
     */
    public function getConfig()
    {
        $name = $this->getBackendName();
        $prefix = 'backend_' . $this->filterName($name) . '_';

        $section = $this->getValue($prefix . 'name');
        $cfg = array(
            'backend'               =>  'ldap',
            'target'                =>  'user',
            'hostname'              =>  $this->getValue($prefix . 'hostname'),
            'root_dn'               =>  $this->getValue($prefix . 'root_dn'),
            'bind_dn'               =>  $this->getValue($prefix . 'bind_dn'),
            'bind_pw'               =>  $this->getValue($prefix . 'bind_pw'),
            'user_class'            =>  $this->getValue($prefix . 'user_class'),
            'user_name_attribute'   =>  $this->getValue($prefix . 'user_name_attribute')
        );
        return array(
            $section => $cfg
        );
    }

    /**
     * Validate the current configuration by creating a backend and requesting the user count
     *
     * @return bool True when the backend is valid, false otherwise
     * @see BaseBackendForm::isValidAuthenticationBacken
     */
    public function isValidAuthenticationBackend()
    {
        try {
            $cfg = $this->getConfig();
            $backendName = 'backend_' . $this->filterName($this->getBackendName()) . '_name';
            $testConn = new LdapUserBackend(
                new Zend_Config($cfg[$this->getValue($backendName)])
            );

            if ($testConn->getUserCount() === 0) {
                throw new Exception('No Users Found On Directory Server');
            }
        } catch (Exception $exc) {

            $this->addErrorMessage(
                'Connection Validation Failed:'.
                $exc->getMessage()
            );
            return false;
        }
        return true;
    }
}
