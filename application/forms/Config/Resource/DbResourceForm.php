<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Resource;

use Icinga\Web\Form;
use Icinga\Application\Platform;

/**
 * Form class for adding/modifying database resources
 */
class DbResourceForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_resource_db');
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData   The data sent by the user
     */
    public function createElements(array $formData)
    {
        $dbChoices = array();
        if (Platform::hasMysqlSupport()) {
            $dbChoices['mysql'] = 'MySQL';
        }
        if (Platform::hasPostgresqlSupport()) {
            $dbChoices['pgsql'] = 'PostgreSQL';
        }
        if (Platform::hasMssqlSupport()) {
            $dbChoices['mssql'] = 'MSSQL';
        }
        if (Platform::hasOracleSupport()) {
            $dbChoices['oracle'] = 'Oracle';
        }
        if (Platform::hasOciSupport()) {
            $dbChoices['oci'] = 'Oracle (OCI8)';
        }
        $offerPostgres = false;
        if (isset($formData['db'])) {
            if ($formData['db'] === 'pgsql') {
                $offerPostgres = true;
            }
        } elseif (key($dbChoices) === 'pgsql') {
            $offerPostgres = true;
        }
        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Resource Name'),
                'description'   => $this->translate('The unique name of this resource')
            )
        );
        $this->addElement(
            'select',
            'db',
            array(
                'required'      => true,
                'autosubmit'    => true,
                'label'         => $this->translate('Database Type'),
                'description'   => $this->translate('The type of SQL database'),
                'multiOptions'  => $dbChoices
            )
        );
        $this->addElement(
            'text',
            'host',
            array (
                'required'      => true,
                'label'         => $this->translate('Host'),
                'description'   => $this->translate('The hostname of the database'),
                'value'         => 'localhost'
            )
        );
        $this->addElement(
            'number',
            'port',
            array(
                'description'       => $this->translate('The port to use'),
                'label'             => $this->translate('Port'),
                'preserveDefault'   => true,
                'required'          => $offerPostgres,
                'value'             => $offerPostgres ? 5432 : null
            )
        );
        $this->addElement(
            'text',
            'dbname',
            array(
                'required'      => true,
                'label'         => $this->translate('Database Name'),
                'description'   => $this->translate('The name of the database to use')
            )
        );
        $this->addElement(
            'text',
            'username',
            array (
                'required'      => true,
                'label'         => $this->translate('Username'),
                'description'   => $this->translate('The user name to use for authentication')
            )
        );
        $this->addElement(
            'password',
            'password',
            array(
                'required'          => true,
                'renderPassword'    => true,
                'label'             => $this->translate('Password'),
                'description'       => $this->translate('The password to use for authentication')
            )
        );
        $this->addElement(
            'checkbox',
            'persistent',
            array(
                'description'   => $this->translate(
                    'Check this box for persistent database connections. Persistent connections are not closed at the'
                    . ' end of a request, but are cached and re-used. This is experimental'
                ),
                'label'         => $this->translate('Persistent')
            )
        );

        return $this;
    }
}
