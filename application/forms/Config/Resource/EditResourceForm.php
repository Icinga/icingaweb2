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

namespace Icinga\Form\Config\Resource;

use \Zend_Config;
use Icinga\Web\Form;
use Icinga\Logger\Logger;
use Icinga\Web\Form\Decorator\HelpText;
use Icinga\Data\ResourceFactory;

/**
 * Form for modifying a monitoring backend
 */
class EditResourceForm extends Form
{
    /**
     * The currently edited resource.
     *
     * @var Zend_Config
     */
    private $resource;

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $oldName = '';

    /**
     * Return the current resource name.
     *
     * @param string $name
     *
     * @return void|\Zend_Form
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->getValue('resource_all_name');
    }

    /**
     * Set the original name of the resource. This value is persisted using
     * a hidden field.
     *
     * @param $name
     */
    public function setOldName($name)
    {
        $this->oldName = $name;
    }

    /**
     * Get the resource name that was initially set.
     */
    public function getOldName()
    {
        return $this->getValue('resource_all_name_old');
    }

    private function addDbForm()
    {
        $this->addElement(
            'select',
            'resource_db_db',
            array(
                'label'         =>  'Database Type',
                'value'         =>  $this->getResource()->get('db', 'mysql'),
                'required'      =>  true,
                'helptext'      =>  'The type of SQL database you want to create.',
                'multiOptions'  =>  array(
                    'mysql'         => 'MySQL',
                    'pgsql'         => 'PostgreSQL'
                    //'oracle'        => 'Oracle'
                )
            )
        );

        $this->addElement(
            'text',
            'resource_db_host',
            array (
                'label'     =>  'Host',
                'value'     =>  $this->getResource()->get('host', 'localhost'),
                'required'  =>  true,
                'helptext'  =>  'The hostname of the database.'
            )
        );

        $this->addElement(
            'text',
            'resource_db_port',
            array(
                'label'     => 'Port',
                'value'     => $this->getResource()->get('port', 3306),
                'required'  => true,
                'validators' => array(
                    array('regex', false, '/^[0-9]+$/')
                ),
                'helptext'  => 'The port number to use.'
            )
        );

        $this->addElement(
            'text',
            'resource_db_dbname',
            array(
                'label'     => 'Database Name',
                'value'     => $this->getResource()->get('dbname', ''),
                'required'  => true,
                'helptext'  => 'The name of the database to use'
            )
        );

        $this->addElement(
            'text',
            'resource_db_username',
            array (
                'label'     =>  'Username',
                'value'     =>  $this->getResource()->get('username', ''),
                'required'  =>  true,
                'helptext'  =>  'The user name to use for authentication.'
            )
        );

        $this->addElement(
            'password',
            'resource_db_password',
            array(
                'label'             => 'Password',
                'renderPassword'    => true,
                'value'             => $this->getResource()->get('password', ''),
                'helptext'          => 'The password to use for authentication',
                'required'          => true
            )
        );
    }

    private function addStatusdatForm()
    {
        $this->addElement(
            'text',
            'resource_statusdat_status_file',
            array(
                'label'     =>  'Status.dat File',
                'value'     =>  $this->getResource()->get('status_file', '/usr/local/icinga/var/status.dat'),
                'required'  =>  true,
                'helptext'  =>  'Location of your icinga status.dat file'
            )
        );
        $this->addElement(
            'text',
            'resource_statusdat_object_file',
            array(
                'label'     => 'Objects.cache File',
                'value'     =>  $this->getResource()->get('status_file', '/usr/local/icinga/var/objects.cache'),
                'required'  =>  true,
                'helptext'  =>  'Location of your icinga objects.cache file'
            )
        );
    }

    private function addLivestatusForm()
    {
        $this->addElement(
            'text',
            'resource_livestatus_socket',
            array(
                'label'     => 'Livestatus Socket Location',
                'required'  =>  true,
                'helptext'  =>  'The path to your livestatus socket used for querying monitoring data',
                'value'     =>  $this->getResource()->socket,
            )
        );
    }

    private function addLdapForm()
    {
        $this->addElement(
            'text',
            'resource_ldap_hostname',
            array(
                'label'         => 'LDAP Server Host',
                'allowEmpty'    =>  false,
                'value'         => $this->getResource()->get('hostname', 'localhost'),
                'helptext'      => 'The hostname or address of the LDAP server to use for authentication',
                'required'      => true
            )
        );

        $this->addElement(
            'text',
            'resource_ldap_root_dn',
            array(
                'label'     => 'LDAP Root DN',
                'value'     => $this->getResource()->get('root_dn', 'ou=people,dc=icinga,dc=org'),
                'helptext'  => 'The path where users can be found on the ldap server',
                'required'  => true
            )
        );

        $this->addElement(
            'text',
            'resource_ldap_bind_dn',
            array(
                'label'     => 'LDAP Bind DN',
                'value'     => $this->getResource()->get('bind_dn', 'cn=admin,cn=config'),
                'helptext'  => 'The user dn to use for querying the ldap server',
                'required'  => true
            )
        );

        $this->addElement(
            'password',
            'resource_ldap_bind_pw',
            array(
                'label'             => 'LDAP Bind Password',
                'renderPassword'    => true,
                'value'             => $this->getResource()->get('bind_pw', ''),
                'helptext'          => 'The password to use for querying the ldap server',
                'required'          => true
            )
        );
    }

    /**
     * Set the resource configuration to edit.
     *
     * @param Zend_Config $resource
     */
    public function setResource(Zend_Config $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Get the current resource configuration.
     *
     * @return Zend_Config
     */
    public function getResource()
    {
        if (!isset($this->resource)) {
            // Init empty resource
            $this->resource = new Zend_Config(
                array('type' => 'db')
            );
        }
        return $this->resource;
    }

    /**
     * Add a field to change the resource name and one hidden field
     * to save the previous resource name.
     */
    private function addNameFields()
    {
        $this->addElement(
            'text',
            'resource_all_name',
            array(
                'label'     => 'Resource Name',
                'value'     => $this->name,
                'helptext'  => 'The unique name of this resource',
                'required'  => true
            )
        );
        $this->addElement(
            'hidden',
            'resource_all_name_old',
            array(
                'value' => $this->oldName
            )
        );
    }

    /**
     * Add checkbox at the beginning of the form which allows to skip logic connection validation
     */
    private function addForceCreationCheckbox()
    {
        $checkbox = new \Zend_Form_Element_Checkbox(
            array(
                'name'      =>  'backend_force_creation',
                'label'     =>  'Force Changes',
                'helptext'  =>  'Check this box to enforce changes without connectivity validation',
                'order'     =>  0
            )
        );
        $checkbox->addDecorator(new HelpText());
        $this->addElement($checkbox);
    }

    /**
     * Add a select box for choosing the type to use for this backend
     */
    private function addTypeSelectionBox()
    {
        $this->addElement(
            'select',
            'resource_type',
            array(
                'label'         =>  'Resource Type',
                'value'         =>  $this->getResource()->type,
                'required'      =>  true,
                'helptext'      =>  'The type of resource.',
                'multiOptions'  =>  array(
                    'db'            => 'SQL Database',
                    'ldap'          => 'Ldap',
                    'statusdat'     => 'Status.dat',
                    'livestatus'    => 'Livestatus'
                )
            )
        );
        $this->enableAutoSubmit(array('resource_type'));
    }

    /**
     * Validate this form with the Zend validation mechanism and perform a validation of the connection.
     *
     * If validation fails, the 'backend_force_creation' checkbox is prepended to the form to allow users to
     * skip the logic connection validation.
     *
     * @param array $data       The form input to validate
     *
     * @return bool             True when validation succeeded, false if not
     */
    public function isValid($data)
    {
        if (!parent::isValid($data)) {
            return false;
        }
        if ($this->getRequest()->getPost('backend_force_creation')) {
            return true;
        }
        if (!$this->isValidResource()) {
            $this->addForceCreationCheckbox();
            return false;
        }
        return true;
    }

    /**
     * Test if the changed resource is a valid resource, by instantiating it and
     * checking if connection is possible.
     *
     * @return bool     True when connection to the resource is possible.
     */
    private function isValidResource()
    {
        try {
            $config = $this->getConfig();
            switch ($config->type) {
                case 'db':
                    $resource = ResourceFactory::createResource($config);
                    $resource->getConnection()->getConnection();
                    break;
                case 'statusdat':
                    if (!file_exists($config->object_file) || !file_exists($config->status_file)) {
                        $this->addErrorMessage(
                            'Connectivity validation failed, the provided file or socket does not exist.'
                        );
                        return false;
                    }
                    break;
                case 'livestatus':
                    // TODO: Implement check
                    break;
                case 'ldap':
                    $resource = ResourceFactory::createResource($config);
                    $resource->connect();
                    break;
            }
        } catch (\Exception $exc) {
            $this->addErrorMessage('Connectivity validation failed, connection to the given resource not possible.');
            return false;
        }
        return true;
    }

    public function create()
    {
        $this->addNameFields();
        $this->addTypeSelectionBox();
        switch ($this->getRequest()->getParam('resource_type', $this->getResource()->type)) {
            case 'db':
                $this->addDbForm();
                break;
            case 'statusdat':
                $this->addStatusdatForm();
                break;
            case 'livestatus':
                $this->addLivestatusForm();
                break;
            case 'ldap':
                $this->addLdapForm();
                break;
        }
        $this->setSubmitLabel('{{SAVE_ICON}} Save Changes');
    }

    /**
     * Return a configuration containing the backend settings entered in this form
     *
     * @return Zend_Config  The updated configuration for this backend
     */
    public function getConfig()
    {
        $values = $this->getValues();
        $type = $values['resource_type'];
        $result = array('type' => $type);
        foreach ($values as $key => $value) {
            if ($key !== 'resource_type' && $key !== 'resource_all_name' && $key !== 'resource_all_name_old') {
                $configKey = explode('_', $key, 3);
                if (sizeof($configKey) < 3) {
                    Logger::warning('EditResourceForm: invalid form key "' . $key . '" was ignored.');
                    continue;
                }
                $result[$configKey[2]] = $value;
            }
        }
        return new Zend_Config($result);
    }
}
