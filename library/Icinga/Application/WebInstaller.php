<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Exception;
use Zend_Config;
use PDOException;
use Icinga\Web\Setup\DbTool;
use Icinga\Web\Setup\Installer;
use Icinga\Data\ResourceFactory;
use Icinga\Config\PreservingIniWriter;
use Icinga\Authentication\Backend\DbUserBackend;

/**
 * Icinga Web 2 Installer
 */
class WebInstaller implements Installer
{
    /**
     * The setup wizard's page data
     *
     * @var array
     */
    protected $pageData;

    /**
     * The report entries
     *
     * @var array
     */
    protected $report;

    /**
     * Create a new web installer
     *
     * @param   array   $pageData   The setup wizard's page data
     */
    public function __construct(array $pageData)
    {
        $this->pageData = $pageData;
        $this->report = array();
    }

    /**
     * @see Installer::run()
     */
    public function run()
    {
        $success = true;

        if (isset($this->pageData['setup_db_resource'])
            && ! $this->pageData['setup_db_resource']['skip_validation']
            && (false === isset($this->pageData['setup_database_creation'])
                || ! $this->pageData['setup_database_creation']['skip_validation']
            )
        ) {
            try {
                $this->setupDatabase();
            } catch (Exception $e) {
                $this->log(sprintf(t('Failed to set up the database: %s'), $e->getMessage()), false);
                return false; // Bail out as early as possible as not being able to setup the database is fatal
            }

            $this->log(t('The database has been successfully set up!'));
        }

        $configIniPath = Config::resolvePath('config.ini');
        try {
            $this->writeConfigIni($configIniPath);
            $this->log(sprintf(t('Successfully created: %s'), $configIniPath));
        } catch (Exception $e) {
            $success = false;
            $this->log(sprintf(t('Unable to create: %s (%s)'), $configIniPath, $e->getMessage()), false);
        }

        $resourcesIniPath = Config::resolvePath('resources.ini');
        try {
            $this->writeResourcesIni($resourcesIniPath);
            $this->log(sprintf(t('Successfully created: %s'), $resourcesIniPath));
        } catch (Exception $e) {
            $success = false;
            $this->log(sprintf(t('Unable to create: %s (%s)'), $resourcesIniPath, $e->getMessage()), false);
        }

        $authenticationIniPath = Config::resolvePath('authentication.ini');
        try {
            $this->writeAuthenticationIni($authenticationIniPath);
            $this->log(sprintf(t('Successfully created: %s'), $authenticationIniPath));
        } catch (Exception $e) {
            $success = false;
            $this->log(sprintf(t('Unable to create: %s (%s)'), $authenticationIniPath, $e->getMessage()), false);
        }

        try {
            $this->setupAdminAccount();
            $this->log(t('Successfully defined initial administrative account.'));
        } catch (Exception $e) {
            $success = false;
            $this->log(sprintf(t('Failed to define initial administrative account: %s'), $e->getMessage()), false);
        }

        return $success;
    }

    /**
     * Write application configuration to the given filepath
     *
     * @param   string      $configPath
     */
    protected function writeConfigIni($configPath)
    {
        $preferencesConfig = array();
        $preferencesConfig['type'] = $this->pageData['setup_preferences_type']['type'];
        if ($this->pageData['setup_preferences_type']['type'] === 'db') {
            $preferencesConfig['resource'] = $this->pageData['setup_db_resource']['name'];
        }

        $loggingConfig = array();
        $loggingConfig['log'] = $this->pageData['setup_general_config']['logging_log'];
        if ($this->pageData['setup_general_config']['logging_log'] !== 'none') {
            $loggingConfig['level'] = $this->pageData['setup_general_config']['logging_level'];
            if ($this->pageData['setup_general_config']['logging_log'] === 'syslog') {
                $loggingConfig['application'] = $this->pageData['setup_general_config']['logging_application'];
                //$loggingConfig['facility'] = $this->pageData['setup_general_config']['logging_facility'];
            } else { // $this->pageData['setup_general_config']['logging_log'] === 'file'
                $loggingConfig['file'] = $this->pageData['setup_general_config']['logging_file'];
            }
        }

        $config = array(
            'global'        => array(
                'modulepath'    => $this->pageData['setup_general_config']['global_modulePath']
            ),
            'preferences'   => $preferencesConfig,
            'logging'       => $loggingConfig
        );

        $writer = new PreservingIniWriter(array('config' => new Zend_Config($config), 'filename' => $configPath));
        $writer->write();
    }

    /**
     * Write resource configuration to the given filepath
     *
     * @param   string      $configPath
     */
    protected function writeResourcesIni($configPath)
    {
        $resourceConfig = array();
        if (isset($this->pageData['setup_db_resource'])) {
            $resourceConfig[$this->pageData['setup_db_resource']['name']] = array(
                'type'      => $this->pageData['setup_db_resource']['type'],
                'db'        => $this->pageData['setup_db_resource']['db'],
                'host'      => $this->pageData['setup_db_resource']['host'],
                'port'      => $this->pageData['setup_db_resource']['port'],
                'dbname'    => $this->pageData['setup_db_resource']['dbname'],
                'username'  => $this->pageData['setup_db_resource']['username'],
                'password'  => $this->pageData['setup_db_resource']['password']
            );
        }

        if (isset($this->pageData['setup_ldap_resource'])) {
            $resourceConfig[$this->pageData['setup_ldap_resource']['name']] = array(
                'type'      => $this->pageData['setup_ldap_resource']['type'],
                'hostname'  => $this->pageData['setup_ldap_resource']['hostname'],
                'port'      => $this->pageData['setup_ldap_resource']['port'],
                'root_dn'   => $this->pageData['setup_ldap_resource']['root_dn'],
                'bind_dn'   => $this->pageData['setup_ldap_resource']['bind_dn'],
                'bind_pw'   => $this->pageData['setup_ldap_resource']['bind_pw']
            );
        }

        if (empty($resourceConfig)) {
            return; // No need to write nothing :)
        }

        $writer = new PreservingIniWriter(array(
            'config'    => new Zend_Config($resourceConfig),
            'filename'  => $configPath
        ));
        $writer->write();
    }

    /**
     * Write authentication backend configuration to the given filepath
     *
     * @param   string      $configPath
     */
    protected function writeAuthenticationIni($configPath)
    {
        $backendConfig = array();
        if ($this->pageData['setup_authentication_type']['type'] === 'db') {
            $backendConfig[$this->pageData['setup_authentication_backend']['name']] = array(
                'backend'   => $this->pageData['setup_authentication_backend']['backend'],
                'resource'  => $this->pageData['setup_db_resource']['name']
            );
        } elseif ($this->pageData['setup_authentication_type']['type'] === 'ldap') {
            $backendConfig[$this->pageData['setup_authentication_backend']['backend']] = array(
                'backend'               => $this->pageData['setup_authentication_backend']['backend'],
                'resource'              => $this->pageData['setup_ldap_resource']['name'],
                'base_dn'               => $this->pageData['setup_authentication_backend']['base_dn'],
                'user_class'            => $this->pageData['setup_authentication_backend']['user_class'],
                'user_name_attribute'   => $this->pageData['setup_authentication_backend']['user_name_attribute']
            );
        } else { // $this->pageData['setup_authentication_type']['type'] === 'autologin'
            $backendConfig[$this->pageData['setup_authentication_backend']['name']] = array(
                'backend'               => $this->pageData['setup_authentication_backend']['backend'],
                'strip_username_regexp' => $this->pageData['setup_authentication_backend']['strip_username_regexp']
            );
        }

        $writer = new PreservingIniWriter(array(
            'config'    => new Zend_Config($backendConfig),
            'filename'  => $configPath
        ));
        $writer->write();
    }

    /**
     * Setup the database
     */
    protected function setupDatabase()
    {
        $resourceConfig = $this->pageData['setup_db_resource'];
        if (isset($this->pageData['setup_database_creation'])) {
            $resourceConfig['username'] = $this->pageData['setup_database_creation']['username'];
            $resourceConfig['password'] = $this->pageData['setup_database_creation']['password'];
        }

        $db = new DbTool($resourceConfig);
        if ($resourceConfig['db'] === 'mysql') {
            $this->setupMysqlDatabase($db);
        } elseif ($resourceConfig['db'] === 'pgsql') {
            $this->setupPgsqlDatabase($db);
        }
    }

    /**
     * Setup a MySQL database
     *
     * @param   DbTool      $db     The database connection wrapper to use
     */
    private function setupMysqlDatabase(DbTool $db)
    {
        try {
            $db->connectToDb();
            $this->log(sprintf(
                t('Successfully connected to existing database "%s"...'),
                $this->pageData['setup_db_resource']['dbname']
            ));
        } catch (PDOException $e) {
            $db->connectToHost();
            $this->log(sprintf(
                t('Creating new database "%s"...'),
                $this->pageData['setup_db_resource']['dbname']
            ));
            $db->exec('CREATE DATABASE ' . $db->quoteIdentifier($this->pageData['setup_db_resource']['dbname']));
            $db->reconnect($this->pageData['setup_db_resource']['dbname']);
        }

        if ($db->hasLogin($this->pageData['setup_db_resource']['username'])) {
            $this->log(sprintf(
                t('Login "%s" already exists...'),
                $this->pageData['setup_db_resource']['username']
            ));
        } else {
            $this->log(sprintf(
                t('Creating login "%s"...'),
                $this->pageData['setup_db_resource']['username']
            ));
            $db->addLogin(
                $this->pageData['setup_db_resource']['username'],
                $this->pageData['setup_db_resource']['password']
            );
        }

        if (array_search('account', $db->listTables()) !== false) {
            $this->log(t('Database schema already exists...'));
        } else {
            $this->log(t('Creating database schema...'));
            $db->import(Icinga::app()->getApplicationDir() . '/../etc/schema/mysql.sql');
        }

        $privileges = array('SELECT', 'INSERT', 'UPDATE', 'DELETE', 'EXECUTE', 'CREATE TEMPORARY TABLES');
        if ($db->checkPrivileges(array_merge($privileges, array('GRANT OPTION')))) {
            $this->log(sprintf(
                t('Granting required privileges to login "%s"...'),
                $this->pageData['setup_db_resource']['username']
            ));
            $db->exec(sprintf(
                "GRANT %s ON %s.* TO %s@%s",
                join(',', $privileges),
                $db->quoteIdentifier($this->pageData['setup_db_resource']['dbname']),
                $db->quoteIdentifier($this->pageData['setup_db_resource']['username']),
                $db->quoteIdentifier(Platform::getFqdn())
            ));
        }
    }

    /**
     * Setup a PostgreSQL database
     *
     * @param   DbTool      $db     The database connection wrapper to use
     */
    private function setupPgsqlDatabase(DbTool $db)
    {
        try {
            $db->connectToDb();
            $this->log(sprintf(
                t('Successfully connected to existing database "%s"...'),
                $this->pageData['setup_db_resource']['dbname']
            ));
        } catch (PDOException $e) {
            $db->connectToHost();
            $this->log(sprintf(
                t('Creating new database "%s"...'),
                $this->pageData['setup_db_resource']['dbname']
            ));
            $db->exec('CREATE DATABASE ' . $db->quoteIdentifier($this->pageData['setup_db_resource']['dbname']));
            $db->reconnect($this->pageData['setup_db_resource']['dbname']);
        }

        if ($db->hasLogin($this->pageData['setup_db_resource']['username'])) {
            $this->log(sprintf(
                t('Login "%s" already exists...'),
                $this->pageData['setup_db_resource']['username']
            ));
        } else {
            $this->log(sprintf(
                t('Creating login "%s"...'),
                $this->pageData['setup_db_resource']['username']
            ));
            $db->addLogin(
                $this->pageData['setup_db_resource']['username'],
                $this->pageData['setup_db_resource']['password']
            );
        }

        if (array_search('account', $db->listTables()) !== false) {
            $this->log(t('Database schema already exists...'));
        } else {
            $this->log(t('Creating database schema...'));
            $db->import(Icinga::app()->getApplicationDir() . '/../etc/schema/pgsql.sql');
        }

        $privileges = array('SELECT', 'INSERT', 'UPDATE', 'DELETE');
        if ($db->checkPrivileges(array_merge($privileges, array('GRANT OPTION')))) {
            $this->log(sprintf(
                t('Granting required privileges to login "%s"...'),
                $this->pageData['setup_db_resource']['username']
            ));
            $db->exec(sprintf(
                "GRANT %s ON TABLE account TO %s",
                join(',', $privileges),
                $db->quoteIdentifier($this->pageData['setup_db_resource']['username'])
            ));
            $db->exec(sprintf(
                "GRANT %s ON TABLE preference TO %s",
                join(',', $privileges),
                $db->quoteIdentifier($this->pageData['setup_db_resource']['username'])
            ));
        }
    }

    /**
     * Define the initial administrative account
     */
    protected function setupAdminAccount()
    {
        if ($this->pageData['setup_admin_account']['user_type'] === 'new_user'
            && ! $this->pageData['setup_db_resource']['skip_validation']
            && (false === isset($this->pageData['setup_database_creation'])
                || ! $this->pageData['setup_database_creation']['skip_validation']
            )
        ) {
            $backend = new DbUserBackend(
                ResourceFactory::createResource(new Zend_Config($this->pageData['setup_db_resource']))
            );

            if (array_search($this->pageData['setup_admin_account']['new_user'], $backend->listUsers()) === false) {
                $backend->addUser(
                    $this->pageData['setup_admin_account']['new_user'],
                    $this->pageData['setup_admin_account']['new_user_password']
                );
            }
        }
    }

    /**
     * @see Installer::getSummary()
     */
    public function getSummary()
    {
        $summary = $this->pageData;
        if (isset($this->pageData['setup_db_resource'])) {
            $resourceConfig = $this->pageData['setup_db_resource'];
            if (isset($this->pageData['setup_database_creation'])) {
                $resourceConfig['username'] = $this->pageData['setup_database_creation']['username'];
                $resourceConfig['password'] = $this->pageData['setup_database_creation']['password'];
            }

            $db = new DbTool($resourceConfig);
            try {
                $db->connectToDb();
                if (array_search('account', $db->listTables()) === false) {
                    $message = sprintf(
                        t(
                            'The database user "%s" will be used to setup the missing'
                            . ' schema required by Icinga Web 2 in database "%s".'
                        ),
                        $resourceConfig['username'],
                        $resourceConfig['dbname']
                    );
                } else {
                    $message = sprintf(
                        t('The database "%s" already seems to be fully set up. No action required.'),
                        $resourceConfig['dbname']
                    );
                }
            } catch (PDOException $e) {
                try {
                    $db->connectToHost();
                    if ($db->hasLogin($this->pageData['setup_db_resource']['username'])) {
                        $message = sprintf(
                            t(
                                'The database user "%s" will be used to create the missing '
                                . 'database "%s" with the schema required by Icinga Web 2.'
                            ),
                            $resourceConfig['username'],
                            $resourceConfig['dbname']
                        );
                    } else {
                        $message = sprintf(
                            t(
                                'The database user "%s" will be used to create the missing database "%s" '
                                . 'with the schema required by Icinga Web 2 and a new login called "%s".'
                            ),
                            $resourceConfig['username'],
                            $resourceConfig['dbname'],
                            $this->pageData['setup_db_resource']['username']
                        );
                    }
                } catch (PDOException $e) {
                    $message = t(
                        'No connection to database host possible. You\'ll need to setup the'
                        . ' database with the schema required by Icinga Web 2 manually.'
                    );
                }
            }

            $summary['database_info'] = $message;
        }

        return $summary;
    }

    /**
     * @see Installer::getReport()
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * Add a message to the report
     *
     * @param   string  $message    The message to add
     * @param   bool    $success    Whether the message represents a success (true) or a failure (false)
     */
    protected function log($message, $success = true)
    {
        $this->report[] = (object) array(
            'state'     => (bool) $success,
            'message'   => $message
        );
    }
}
