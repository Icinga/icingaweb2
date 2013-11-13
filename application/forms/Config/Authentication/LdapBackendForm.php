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

use \Exception;
use Icinga\Data\ResourceFactory;
use \Zend_Config;
use \Icinga\Web\Form;
use \Icinga\Authentication\Backend\LdapUserBackend;
use \Icinga\Protocol\Ldap\Connection as LdapConnection;

/**
 * Form for adding or modifying LDAP authentication backends
 */
class LdapBackendForm extends BaseBackendForm
{
    /**
     * Create this form and add all required elements
     *
     * @param $options      Only useful for testing purposes:
     *                          'resources' => All available resources.
     *
     * @see Form::create()
     */
    public function create($options = array())
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
            'select',
            'backend_' . $name . '_resource',
            array(
                'label'         =>  'Database Connection',
                'required'      =>  true,
                'allowEmpty'    =>  false,
                'helptext'      => 'The database connection to use for authenticating with this provider',
                'value'         =>  $this->getBackend()->get('resource'),
                'multiOptions'  =>  array_key_exists('resources', $options) ?
                                        $options['resources'] : $this->getLdapResources()
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
            'target'              =>  'user',
            'resource'            =>  $this->getValue($prefix . 'resource'),
            'user_class'          =>  $this->getValue($prefix . 'user_class'),
            'user_name_attribute' =>  $this->getValue($prefix . 'user_name_attribute')
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
            $backendConfig = new Zend_Config($cfg[$this->getValue($backendName)]);
            $testConn = new LdapUserBackend($backendConfig);
            if ($testConn->getUserCount() === 0) {
                throw new Exception('No Users Found On Directory Server');
            }
        } catch (Exception $exc) {
            $this->addErrorMessage(
                'Connection Validation Failed:' . $exc->getMessage()
            );
            return false;
        }
        return true;
    }

    private function getLdapResources()
    {
        $res = ResourceFactory::getResourceConfigs('ldap')->toArray();
        foreach ($res as $key => $value) {
            $res[$key] = $key;
        }
        return $res;
    }
}
