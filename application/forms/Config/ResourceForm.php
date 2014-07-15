<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config;

use Exception;
use Zend_Config;
use Zend_Form_Element_Checkbox;
use Icinga\Web\Form;
use Icinga\Data\ResourceFactory;
use Icinga\Web\Form\Element\Number;
use Icinga\Web\Form\Decorator\HelpText;

class ResourceForm extends Form
{
    /**
     * The resource
     *
     * @var Zend_Config
     */
    protected $resource;

    /**
     * The (new) name of the resource
     *
     * @var string
     */
    protected $name;

    /**
     * The old name of the resource
     *
     * @var string
     */
    protected $oldName;

    /**
     * Set the current resource name
     *
     * @param   string      $name   The name to set
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the current resource name
     *
     * @return null|string
     */
    public function getName()
    {
        $name = $this->getValue('resource_all_name');
        if (!$name) {
            return $this->name;
        }

        return $name;
    }

    /**
     * Set the original name of the resource
     *
     * @param   string      $name   The name to set
     */
    public function setOldName($name)
    {
        $this->oldName = $name;
    }

    /**
     * Get the resource name that was initially set
     *
     * @return  null|string
     */
    public function getOldName()
    {
        $oldName = $this->getValue('resource_all_name_old');
        if (!$oldName) {
            return $this->oldName;
        }

        return $oldName;
    }

    /**
     * Set the resource configuration to edit.
     *
     * @param   Zend_Config     $resource   The config to set
     */
    public function setResource(Zend_Config $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Get the current resource configuration.
     *
     * @return  Zend_Config
     */
    public function getResource()
    {
        if (!isset($this->resource)) {
            $this->resource = new Zend_Config(array('type' => 'db'));
        }

        return $this->resource;
    }

    protected function addDbForm()
    {
        $this->addElement(
            'select',
            'resource_db_db',
            array(
                'required'      => true,
                'label'         => t('Database Type'),
                'helptext'      => t('The type of SQL database you want to create.'),
                'value'         => $this->getResource()->get('db', 'mysql'),
                'multiOptions'  => array(
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
                'required'  => true,
                'label'     => t('Host'),
                'helptext'  => t('The hostname of the database.'),
                'value'     => $this->getResource()->get('host', 'localhost')
            )
        );

        $this->addElement(
            new Number(
                array(
                    'name'      => 'resource_db_port',
                    'required'  => true,
                    'label'     => t('Port'),
                    'helptext'  => t('The port to use.'),
                    'value'     => $this->getResource()->get('port', 3306)
                )
            )
        );

        $this->addElement(
            'text',
            'resource_db_dbname',
            array(
                'required'  => true,
                'label'     => t('Database Name'),
                'helptext'  => t('The name of the database to use'),
                'value'     => $this->getResource()->get('dbname', '')
            )
        );

        $this->addElement(
            'text',
            'resource_db_username',
            array (
                'required'  => true,
                'label'     => t('Username'),
                'helptext'  => t('The user name to use for authentication.'),
                'value'     => $this->getResource()->get('username', '')
            )
        );

        $this->addElement(
            'password',
            'resource_db_password',
            array(
                'required'          => true,
                'renderPassword'    => true,
                'label'             => t('Password'),
                'helptext'          => t('The password to use for authentication'),
                'value'             => $this->getResource()->get('password', '')
            )
        );
    }

    protected function addStatusdatForm()
    {
        $this->addElement(
            'text',
            'resource_statusdat_status_file',
            array(
                'required'  => true,
                'label'     => t('Filepath'),
                'helptext'  => t('Location of your icinga status.dat file'),
                'value'     => $this->getResource()->get('status_file', '/usr/local/icinga/var/status.dat')
            )
        );

        $this->addElement(
            'text',
            'resource_statusdat_object_file',
            array(
                'required'  => true,
                'label'     => t('Filepath'),
                'helptext'  => t('Location of your icinga objects.cache file'),
                'value'     => $this->getResource()->get('status_file', '/usr/local/icinga/var/objects.cache')
            )
        );
    }

    protected function addLivestatusForm()
    {
        $this->addElement(
            'text',
            'resource_livestatus_socket',
            array(
                'required'  => true,
                'label'     => t('Socket'),
                'helptext'  => t('The path to your livestatus socket used for querying monitoring data'),
                'value'     => $this->getResource()->get('socket', '/usr/local/icinga/var/rw/livestatus')
            )
        );
    }

    protected function addLdapForm()
    {
        $this->addElement(
            'text',
            'resource_ldap_hostname',
            array(
                'required'      => true,
                'allowEmpty'    => false,
                'label'         => t('Host'),
                'helptext'      => t('The hostname or address of the LDAP server to use for authentication'),
                'value'         => $this->getResource()->get('hostname', 'localhost')
            )
        );

        $this->addElement(
            'text',
            'resource_ldap_root_dn',
            array(
                'required'  => true,
                'label'     => t('Root DN'),
                'helptext'  => t('The path where users can be found on the ldap server'),
                'value'     => $this->getResource()->get('root_dn', 'ou=people,dc=icinga,dc=org')
            )
        );

        $this->addElement(
            'text',
            'resource_ldap_bind_dn',
            array(
                'required'  => true,
                'label'     => t('Bind DN'),
                'helptext'  => t('The user dn to use for querying the ldap server'),
                'value'     => $this->getResource()->get('bind_dn', 'cn=admin,cn=config')
            )
        );

        $this->addElement(
            'password',
            'resource_ldap_bind_pw',
            array(
                'required'          => true,
                'renderPassword'    => true,
                'label'             => t('Bind Password'),
                'helptext'          => t('The password to use for querying the ldap server'),
                'value'             => $this->getResource()->get('bind_pw', '')
            )
        );
    }

    protected function addFileForm()
    {
        $this->addElement(
            'text',
            'resource_file_filename',
            array(
                'required'  => true,
                'label'     => t('Filepath'),
                'helptext'  => t('The filename to fetch information from'),
                'value'     => $this->getResource()->get('filename', '')
            )
        );

        $this->addElement(
            'text',
            'resource_file_fields',
            array(
                'required'  => true,
                'label'     => t('Pattern'),
                'helptext'  => t('The regular expression by which to identify columns'),
                'value'     => $this->getResource()->get('fields', '')
            )
        );
    }

    protected function addNameFields()
    {
        $this->addElement(
            'text',
            'resource_all_name',
            array(
                'required'  => true,
                'label'     => t('Resource Name'),
                'helptext'  => t('The unique name of this resource'),
                'value'     => $this->getName()
            )
        );

        $this->addElement(
            'hidden',
            'resource_all_name_old',
            array(
                'value' => $this->getOldName()
            )
        );
    }

    /**
     * Add checkbox at the beginning of the form which allows to skip connection validation
     */
    protected function addForceCreationCheckbox()
    {
        $checkbox = new Zend_Form_Element_Checkbox(
            array(
                'order'     => 0,
                'name'      => 'resource_force_creation',
                'label'     => t('Force Changes'),
                'helptext'  => t('Check this box to enforce changes without connectivity validation')
            )
        );
        $checkbox->addDecorator(new HelpText());
        $this->addElement($checkbox);
    }

    /**
     * Add a select box for choosing the type to use for this backend
     */
    protected function addTypeSelectionBox()
    {
        $this->addElement(
            'select',
            'resource_type',
            array(
                'required'      => true,
                'label'         => t('Resource Type'),
                'helptext'      => t('The type of resource'),
                'value'         => $this->getResource()->type,
                'multiOptions'  => array(
                    'db'            => t('SQL Database'),
                    'ldap'          => 'LDAP',
                    'statusdat'     => 'Status.dat',
                    'livestatus'    => 'Livestatus',
                    'file'          => t('File')
                )
            )
        );
        $this->enableAutoSubmit(array('resource_type'));
    }

    /**
     * Validate this form with the Zend validation mechanism and perform a validation of the connection
     *
     * If validation fails, the 'resource_force_creation' checkbox is prepended to the form to allow users to
     * skip the connection validation
     *
     * @param   array   $data   The form input to validate
     *
     * @return  bool            True when validation succeeded, false if not
     */
    public function isValid($data)
    {
        if (!parent::isValid($data)) {
            return false;
        }
        if (isset($data['resource_force_creation']) && $data['resource_force_creation']) {
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
     * checking if a connection is possible
     *
     * @return  bool    True when a connection to the resource is possible
     */
    public function isValidResource()
    {
        $config = $this->getConfig();

        try {
            switch ($config->type) {
                case 'db':
                    /*
                     * It should be possible to run icingaweb without the pgsql or mysql extension or Zend-Pdo-Classes,
                     * in case they aren't actually used. When the user tries to create a resource that depends on an
                     * uninstalled extension, an error should be displayed.
                     */
                    if ($config->db === 'mysql' && !ResourceFactory::mysqlAvailable()) {
                        $this->addErrorMessage(
                            t('You need to install the php extension "mysql" and the ' .
                              'Zend_Pdo_Mysql classes to use  MySQL database resources.')
                        );
                        return false;
                    }
                    if ($config->db === 'pgsql' && !ResourceFactory::pgsqlAvailable()) {
                        $this->addErrorMessage(
                            t('You need to install the php extension "pgsql" and the ' .
                              'Zend_Pdo_Pgsql classes to use  PostgreSQL database resources.')
                        );
                        return false;
                    }

                    $resource = ResourceFactory::createResource($config);
                    $resource->getConnection()->getConnection();
                    break;
                case 'statusdat':
                    if (!file_exists($config->object_file) || !file_exists($config->status_file)) {
                        $this->addErrorMessage(
                            t('Connectivity validation failed, the provided file does not exist.')
                        );
                        return false;
                    }
                    break;
                case 'livestatus':
                    $resource = ResourceFactory::createResource($config);
                    $resource->connect()->disconnect();
                    break;
                case 'ldap':
                    $resource = ResourceFactory::createResource($config);
                    $resource->connect();
                    break;
                case 'file':
                    if (!file_exists($config->filename)) {
                        $this->addErrorMessage(
                            t('Connectivity validation failed, the provided file does not exist.')
                        );
                        return false;
                    }
                    break;
            }
        } catch (Exception $e) {
            $this->addErrorMessage(t('Connectivity validation failed, connection to the given resource not possible.'));
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
            case 'file':
                $this->addFileForm();
                break;
        }

        $this->setSubmitLabel('{{SAVE_ICON}} Save Changes');
    }

    /**
     * Return a configuration containing the backend settings entered in this form
     *
     * @return  Zend_Config     The updated configuration for this backend
     */
    public function getConfig()
    {
        $values = $this->getValues();

        $result = array('type' => $values['resource_type']);
        foreach ($values as $key => $value) {
            if ($key !== 'resource_type' && $key !== 'resource_all_name' && $key !== 'resource_all_name_old') {
                $configKey = explode('_', $key, 3);
                if (count($configKey) === 3) {
                    $result[$configKey[2]] = $value;
                }
            }
        }

        return new Zend_Config($result);
    }
}
