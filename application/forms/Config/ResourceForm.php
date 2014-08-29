<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config;

use Exception;
use Icinga\Application\Platform;
use Zend_Config;
use Icinga\Web\Form;
use Icinga\Application\Icinga;
use Icinga\Data\ResourceFactory;
use Icinga\Web\Form\Element\Number;
use Icinga\Web\Form\Decorator\HelpText;
use Icinga\Web\Form\Decorator\ElementWrapper;

class ResourceForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_resource');
        $this->setSubmitLabel(t('Save Changes'));
    }

    /**
     * @see Form::createElemeents()
     */
    public function createElements(array $formData)
    {
        $elements = array();
        $elements[] = $this->createElement(
            'text',
            'name',
            array(
                'required'  => true,
                'label'     => t('Resource Name'),
                'helptext'  => t('The unique name of this resource')
            )
        );
        $elements[] = $this->createElement(
            'select',
            'type',
            array(
                'required'      => true,
                'class'         => 'autosubmit',
                'label'         => t('Resource Type'),
                'helptext'      => t('The type of resource'),
                'multiOptions'  => array(
                    'db'            => t('SQL Database'),
                    'ldap'          => 'LDAP',
                    'statusdat'     => 'Status.dat',
                    'livestatus'    => 'Livestatus',
                    'file'          => t('File')
                )
            )
        );

        if (isset($formData['force_creation']) && $formData['force_creation']) {
            // In case the resource name already exists and the checkbox was displayed before
            $elements[] = $this->getForceCreationCheckbox();
        }

        if (false === isset($formData['type']) || $formData['type'] === 'db') {
            return array_merge($elements, $this->getDbElements());
        } elseif ($formData['type'] === 'statusdat') {
            return array_merge($elements, $this->getStatusdatElements());
        } elseif ($formData['type'] === 'livestatus') {
            return array_merge($elements, $this->getLivestatusElements());
        } elseif ($formData['type'] === 'ldap') {
            return array_merge($elements, $this->getLdapElements());
        } elseif ($formData['type'] === 'file') {
            return array_merge($elements, $this->getFileElements());
        }
    }

    /**
     * Return whether the given values are complete/valid and check whether it is possible to connect to the resource
     *
     * If connection validation fails, a checkbox is prepended to the form to allow users to skip it.
     *
     * @param   array   $data   The data to validate
     *
     * @return  bool            Whether the validation succeeded or not
     */
    public function isValid($data)
    {
        if (false === parent::isValid($data)) {
            return false;
        }

        if (
            (false === isset($data['force_creation']) || false == $data['force_creation'])
            && false === $this->isValidResource()
        ) {
            $this->addElement($this->getForceCreationCheckbox());
            return false;
        }

        return true;
    }

    /**
     * Return whether a connection can be established with the current resource configuration values
     *
     * @return  bool    Whether the connection validation was successful or not
     */
    public function isValidResource()
    {
        list($name, $config) = $this->getResourceConfig();

        try {
            switch ($config['type']) {
                case 'db':
                    /*
                     * It should be possible to run icingaweb without the pgsql or mysql extension or Zend-Pdo-Classes,
                     * in case they aren't actually used. When the user tries to create a resource that depends on an
                     * uninstalled extension, an error should be displayed.
                     */
                    if ($config['db'] === 'mysql' && false === Platform::extensionLoaded('mysql')) {
                        $this->addErrorMessage(
                            t('You need to install the php extension "mysql" and the ' .
                              'Zend_Pdo_Mysql classes to use  MySQL database resources.')
                        );
                        return false;
                    }
                    if ($config['db'] === 'pgsql' && false === Platform::extensionLoaded('pgsql')) {
                        $this->addErrorMessage(
                            t('You need to install the php extension "pgsql" and the ' .
                              'Zend_Pdo_Pgsql classes to use  PostgreSQL database resources.')
                        );
                        return false;
                    }

                    $resource = ResourceFactory::createResource(new Zend_Config($config));
                    $resource->getConnection()->getConnection();
                    break;
                case 'statusdat':
                    if (
                        false === file_exists($config['object_file'])
                        || false === file_exists($config['status_file'])
                    ) {
                        $this->addErrorMessage(
                            t('Connectivity validation failed. At least one of the provided files does not exist.')
                        );
                        return false;
                    }
                    break;
                case 'livestatus':
                    $resource = ResourceFactory::createResource(new Zend_Config($config));
                    $resource->connect()->disconnect();
                    break;
                case 'ldap':
                    $resource = ResourceFactory::createResource(new Zend_Config($config));
                    $resource->connect();
                    break;
                case 'file':
                    if (false === file_exists($config['filename'])) {
                        $this->addErrorMessage(t('Connectivity validation failed. The provided file does not exist.'));
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

    /**
     * Return the resource configuration values and its name
     *
     * The first value is the name and the second one the values as array.
     *
     * @return  array
     */
    public function getResourceConfig()
    {
        $values = $this->getValues();
        $name = $values['name'];
        unset($values['name']);
        return array($name, $values);
    }

    /**
     * Populate the form with the given configuration values
     *
     * @param   string  $name       The name of the resource
     * @param   array   $config     The configuration values
     */
    public function setResourceConfig($name, array $config)
    {
        $config['name'] = $name;
        $this->populate($config);
    }

    /**
     * Return a checkbox to be displayed at the beginning of the form
     * which allows the user to skip the connection validation
     *
     * @return  Zend_Form_Element
     */
    protected function getForceCreationCheckbox()
    {
        return $this->createElement(
            'checkbox',
            'force_creation',
            array(
                'order'     => 0,
                'ignore'    => true,
                'label'     => t('Force Changes'),
                'helptext'  => t('Check this box to enforce changes without connectivity validation')
            )
        );
    }

    /**
     * Return all required elements to define a resource of type "db"
     *
     * @return  array
     */
    protected function getDbElements()
    {
        return array(
            $this->createElement(
                'select',
                'db',
                array(
                    'required'      => true,
                    'label'         => t('Database Type'),
                    'helptext'      => t('The type of SQL database'),
                    'multiOptions'  => array(
                        'mysql'         => 'MySQL',
                        'pgsql'         => 'PostgreSQL'
                        //'oracle'        => 'Oracle'
                    )
                )
            ),
            $this->createElement(
                'text',
                'host',
                array (
                    'required'  => true,
                    'label'     => t('Host'),
                    'helptext'  => t('The hostname of the database'),
                    'value'     => 'localhost'
                )
            ),
            new Number(
                array(
                    'required'      => true,
                    'name'          => 'port',
                    'label'         => t('Port'),
                    'helptext'      => t('The port to use'),
                    'value'         => 3306,
                    'decorators'    => array( // The order is important!
                        'ViewHelper',
                        'Errors',
                        new ElementWrapper(),
                        new HelpText()
                    )
                )
            ),
            $this->createElement(
                'text',
                'dbname',
                array(
                    'required'  => true,
                    'label'     => t('Database Name'),
                    'helptext'  => t('The name of the database to use')
                )
            ),
            $this->createElement(
                'text',
                'username',
                array (
                    'required'  => true,
                    'label'     => t('Username'),
                    'helptext'  => t('The user name to use for authentication')
                )
            ),
            $this->createElement(
                'password',
                'password',
                array(
                    'required'          => true,
                    'renderPassword'    => true,
                    'label'             => t('Password'),
                    'helptext'          => t('The password to use for authentication')
                )
            )
        );
    }

    /**
     * Return all required elements to define a resource of type "statusdat"
     *
     * @return  array
     */
    protected function getStatusdatElements()
    {
        return array(
            $this->createElement(
                'text',
                'status_file',
                array(
                    'required'  => true,
                    'label'     => t('Filepath'),
                    'helptext'  => t('Location of your icinga status.dat file'),
                    'value'     => realpath(Icinga::app()->getApplicationDir() . '/../var/status.dat')
                )
            ),
            $this->createElement(
                'text',
                'object_file',
                array(
                    'required'  => true,
                    'label'     => t('Filepath'),
                    'helptext'  => t('Location of your icinga objects.cache file'),
                    'value'     => realpath(Icinga::app()->getApplicationDir() . '/../var/objects.cache')
                )
            )
        );
    }

    /**
     * Return all required elements to define a resource of type "livestatus"
     *
     * @return  array
     */
    protected function getLivestatusElements()
    {
        return array(
            $this->createElement(
                'text',
                'socket',
                array(
                    'required'  => true,
                    'label'     => t('Socket'),
                    'helptext'  => t('The path to your livestatus socket used for querying monitoring data'),
                    'value'     => realpath(Icinga::app()->getApplicationDir() . '/../var/rw/livestatus')
                )
            )
        );
    }

    /**
     * Return all required elements to define a resource of type "ldap"
     *
     * @return  array
     */
    protected function getLdapElements()
    {
        return array(
            $this->createElement(
                'text',
                'hostname',
                array(
                    'required'      => true,
                    'allowEmpty'    => false,
                    'label'         => t('Host'),
                    'helptext'      => t('The hostname or address of the LDAP server to use for authentication'),
                    'value'         => 'localhost'
                )
            ),
            new Number(
                array(
                    'required'      => true,
                    'name'          => 'port',
                    'label'         => t('Port'),
                    'helptext'      => t('The port of the LDAP server to use for authentication'),
                    'value'         => 389,
                    'decorators'    => array( // The order is important!
                        'ViewHelper',
                        'Errors',
                        new ElementWrapper(),
                        new HelpText()
                    )
                )
            ),
            $this->createElement(
                'text',
                'root_dn',
                array(
                    'required'  => true,
                    'label'     => t('Root DN'),
                    'helptext'  => t('The path where users can be found on the ldap server')
                )
            ),
            $this->createElement(
                'text',
                'bind_dn',
                array(
                    'required'  => true,
                    'label'     => t('Bind DN'),
                    'helptext'  => t('The user dn to use for querying the ldap server')
                )
            ),
            $this->createElement(
                'password',
                'bind_pw',
                array(
                    'required'          => true,
                    'renderPassword'    => true,
                    'label'             => t('Bind Password'),
                    'helptext'          => t('The password to use for querying the ldap server')
                )
            )
        );
    }

    /**
     * Return all required elements to define a resource of type "file"
     *
     * @return  array
     */
    protected function getFileElements()
    {
        return array(
            $this->createElement(
                'text',
                'filename',
                array(
                    'required'  => true,
                    'label'     => t('Filepath'),
                    'helptext'  => t('The filename to fetch information from')
                )
            ),
            $this->createElement(
                'text',
                'fields',
                array(
                    'required'  => true,
                    'label'     => t('Pattern'),
                    'helptext'  => t('The regular expression by which to identify columns')
                )
            )
        );
    }
}
