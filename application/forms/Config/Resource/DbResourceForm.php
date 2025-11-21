<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Resource;

use Icinga\Application\Platform;
use Icinga\Web\Form;

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
        if (Platform::hasIbmSupport()) {
            $dbChoices['ibm'] = 'IBM (DB2)';
        }
        if (Platform::hasOracleSupport()) {
            $dbChoices['oracle'] = 'Oracle';
        }
        if (Platform::hasOciSupport()) {
            $dbChoices['oci'] = 'Oracle (OCI8)';
        }
        if (Platform::hasSqliteSupport()) {
            $dbChoices['sqlite'] = 'SQLite';
        }

        $offerPostgres = false;
        $offerMysql = false;
        $dbChoice = isset($formData['db']) ? $formData['db'] : key($dbChoices);
        if ($dbChoice === 'pgsql') {
            $offerPostgres = true;
        } elseif ($dbChoice === 'mysql') {
            $offerMysql = true;
        }

        if ($dbChoice === 'oracle' || $dbChoice === 'oci') {
            $hostIsRequired = false;
        } else {
            $hostIsRequired = true;
        }

        $socketInfo = '';
        if ($offerPostgres) {
            $socketInfo = $this->translate(
                'For using unix domain sockets, specify the path to the unix domain socket directory'
            );
        } elseif ($offerMysql) {
            $socketInfo = $this->translate(
                'For using unix domain sockets, specify localhost'
            );
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
        if ($dbChoice === 'sqlite') {
            $this->addElement(
                'text',
                'dbname',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Database Name'),
                    'description'   => $this->translate('The name of the database to use')
                )
            );
        } else {
            $this->addElement(
                'text',
                'host',
                array (
                    'required'      => $hostIsRequired,
                    'label'         => $this->translate('Host'),
                    'description'   => $this->translate('The hostname of the database')
                        . ($socketInfo ? '. ' . $socketInfo : ''),
                    'value'         => $hostIsRequired ? 'localhost' : ''
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
                    'description'       => $this->translate('The password to use for authentication'),
                    'autocomplete'      => 'new-password'
                )
            );
            $this->addElement(
                'text',
                'charset',
                array (
                    'description'   => $this->translate('The character set for the database'),
                    'label'         => $this->translate('Character Set')
                )
            );
            $this->addElement(
                'checkbox',
                'use_ssl',
                array(
                    'autosubmit'    => true,
                    'label'         => $this->translate('Use SSL'),
                    'description'   => $this->translate(
                        'Whether to encrypt the connection or to authenticate using certificates'
                    )
                )
            );
            if (isset($formData['use_ssl']) && $formData['use_ssl']) {
                if (
                    defined('\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')
                    || defined('Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')
                ) {
                    $this->addElement(
                        'checkbox',
                        'ssl_do_not_verify_server_cert',
                        array(
                            'label'             => $this->translate('SSL Do Not Verify Server Certificate'),
                            'description'       => $this->translate(
                                'Whether to disable verification of the server certificate'
                            )
                        )
                    );
                }
                $this->addElement(
                    'text',
                    'ssl_key',
                    array(
                        'label'             => $this->translate('SSL Key'),
                        'description'       => $this->translate('The client key file path')
                    )
                );
                $this->addElement(
                    'text',
                    'ssl_cert',
                    array(
                        'label'             => $this->translate('SSL Certificate'),
                        'description'       => $this->translate('The certificate file path')
                    )
                );
                $this->addElement(
                    'text',
                    'ssl_ca',
                    array(
                        'label'             => $this->translate('SSL CA'),
                        'description'       => $this->translate('The CA certificate file path')
                    )
                );
                $this->addElement(
                    'text',
                    'ssl_capath',
                    array(
                        'label'             => $this->translate('SSL CA Path'),
                        'description'       => $this->translate(
                            'The trusted CA certificates in PEM format directory path'
                        )
                    )
                );
                $this->addElement(
                    'text',
                    'ssl_cipher',
                    array(
                        'label'             => $this->translate('SSL Cipher'),
                        'description'       => $this->translate('The list of permissible ciphers')
                    )
                );
            }
        }

        return $this;
    }
}
