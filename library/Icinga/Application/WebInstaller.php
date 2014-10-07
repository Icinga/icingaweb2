<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Exception;
use Zend_Config;
use PDOException;
use Icinga\Web\Setup\DbTool;
use Icinga\Application\Config;
use Icinga\Web\Setup\Installer;
use Icinga\Config\PreservingIniWriter;

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
        $loggingConfig['type'] = $this->pageData['setup_general_config']['logging_type'];
        $loggingConfig['level'] = $this->pageData['setup_general_config']['logging_level'];
        if ($this->pageData['setup_general_config']['logging_type'] === 'syslog') {
            $loggingConfig['application'] = $this->pageData['setup_general_config']['logging_application'];
            $loggingConfig['facility'] = $this->pageData['setup_general_config']['logging_facility'];
        } else { // $this->pageData['setup_general_config']['logging_type'] === 'file'
            $loggingConfig['target'] = $this->pageData['setup_general_config']['logging_target'];
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
                'hostname'  => $this->pageData['setup_ldap_resource']['hostname'],
                'port'      => $this->pageData['setup_ldap_resource']['port'],
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
                //'root_dn'               => $this->pageData['setup_ldap_resource']['root_dn'],
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
     * @see Installer::getSummary()
     */
    public function getSummary()
    {
        $summary = array();

        $prefType = $this->pageData['setup_preferences_type']['type'];
        $authType = $this->pageData['setup_authentication_type']['type'];
        if ($authType !== 'autologin' || $prefType === 'db') {
            $resourceInfo = array();

            if ($authType === 'db' || $prefType === 'db') {
                if ($authType === 'db' && $prefType === 'db') {
                    $resourceInfo[] = t(
                        'The following database will be used to authenticate users and to store preferences.'
                    );
                } elseif ($authType === 'db') {
                    $resourceInfo[] = t('The following database will be used to authenticate users.');
                } else { // $prefType === 'db'
                    $resourceInfo[] = t('The following database will be used to store preferences.');
                }

                $resourceInfo[t('Database')] = array(
                    sprintf(t('Resource Name: %s'), $this->pageData['setup_db_resource']['name']),
                    sprintf(t('Database Type: %s'), $this->pageData['setup_db_resource']['db']),
                    sprintf(t('Host: %s'), $this->pageData['setup_db_resource']['host']),
                    sprintf(t('Port: %s'), $this->pageData['setup_db_resource']['port']),
                    sprintf(t('Database Name: %s'), $this->pageData['setup_db_resource']['dbname']),
                    sprintf(t('Username: %s'), $this->pageData['setup_db_resource']['username']),
                    sprintf(
                        t('Password: %s'),
                        str_repeat('*', strlen($this->pageData['setup_db_resource']['password']))
                    )
                );
            }

            if ($authType === 'ldap') {
                $resourceInfo[] = t('The following LDAP connection will be used to authenticate users.');
                $resourceInfo['LDAP'] = array(
                    sprintf(t('Resource Name: %s'), $this->pageData['setup_ldap_resource']['name']),
                    sprintf(t('Host: %s'), $this->pageData['setup_ldap_resource']['hostname']),
                    sprintf(t('Port: %s'), $this->pageData['setup_ldap_resource']['port']),
                    sprintf(t('Root DN: %s'), $this->pageData['setup_ldap_resource']['root_dn']),
                    sprintf(t('Bind DN: %s'), $this->pageData['setup_ldap_resource']['bind_dn']),
                    sprintf(
                        t('Bind Password: %s'),
                        str_repeat('*', strlen($this->pageData['setup_ldap_resource']['bind_pw']))
                    )
                );
            }

            $summary[tp('Resource', 'Resources', count($resourceInfo) / 2)] = $resourceInfo;
        }

        $adminType = $this->pageData['setup_admin_account']['user_type'];
        $summary[t('Authentication')] = array(
            sprintf(
                t('Users will authenticate using %s.', 'setup.summary.auth'),
                $authType === 'db' ? t('a database', 'setup.summary.auth.type') : (
                    $authType === 'ldap' ? 'LDAP' : t('webserver authentication', 'setup.summary.auth.type')
                )
            ),
            t('Backend Configuration') => $authType === 'db' ? array(
                sprintf(t('Backend Name: %s'), $this->pageData['setup_authentication_backend']['name'])
            ) : ($authType === 'ldap' ? array(
                    sprintf(t('Backend Name: %s'), $this->pageData['setup_authentication_backend']['name']),
                    sprintf(
                        t('LDAP User Object Class: %s'),
                        $this->pageData['setup_authentication_backend']['user_class']
                    ),
                    sprintf(
                        t('LDAP User Name Attribute: %s'),
                        $this->pageData['setup_authentication_backend']['user_name_attribute']
                    )
                ) : array(
                    sprintf(t('Backend Name: %s'), $this->pageData['setup_authentication_backend']['name']),
                    sprintf(
                        t('Backend Domain Pattern: %s'),
                        $this->pageData['setup_authentication_backend']['strip_username_regexp']
                    )
                )
            ),
            t('Initial Administrative Account') => $adminType === 'by_name' || $adminType === 'existing_user'
                ? sprintf(
                    t('Administrative rights will initially be granted to an existing account called "%s".'),
                    $this->pageData['setup_admin_account'][$adminType]
                ) : sprintf(
                    t('Administrative rights will initially be granted to a new account called "%s".'),
                    $this->pageData['setup_admin_account'][$adminType]
                )
        );

        $loggingLevel = $this->pageData['setup_general_config']['logging_level'];
        $loggingType = $this->pageData['setup_general_config']['logging_type'];
        $summary[t('Application Configuration')] = array(
            t('General', 'app.config') => array(
                sprintf(
                    t('Icinga Web 2 will look for modules at: %s'),
                    $this->pageData['setup_general_config']['global_modulePath']
                ),
                sprintf(
                    $prefType === 'ini' ? sprintf(
                        t('Preferences will be stored per user account in INI files at: %s'),
                        Config::$configDir . '/preferences'
                    ) : (
                        $prefType === 'db' ? t('Preferences will be stored using a database.') : (
                            t('Preferences will not be persisted across browser sessions.')
                        )
                    )
                )
            ),
            t('Logging', 'app.config') => array_merge(
                array(
                    sprintf(
                        t('Level: %s', 'app.config.logging'),
                        $loggingLevel === 0 ? t('None', 'app.config.logging.level') : (
                            $loggingLevel === 1 ? t('Error', 'app.config.logging.level') : (
                                $loggingLevel === 2 ? t('Warning', 'app.config.logging.level') : (
                                    $loggingLevel === 3 ? t('Information', 'app.config.logging.level') : (
                                        t('Debug', 'app.config.logging.level')
                                    )
                                )
                            )
                        )
                    ),
                    sprintf(
                        t('Type: %s', 'app.config.logging'),
                        $loggingType === 'syslog' ? 'Syslog' : t('File', 'app.config.logging.type')
                    )
                ),
                $this->pageData['setup_general_config']['logging_type'] === 'syslog' ? array(
                    sprintf(
                        t('Application Prefix: %s'),
                        $this->pageData['setup_general_config']['logging_application']
                    ),
                    sprintf(
                        t('Facility: %s'),
                        $this->pageData['setup_general_config']['logging_facility']
                    )
                ) : array(
                    sprintf(
                        t('Filepath: %s'),
                        $this->pageData['setup_general_config']['logging_target']
                    )
                )
            )
        );

        if (isset($this->pageData['setup_database_creation'])) {
            $resourceConfig = $this->pageData['setup_db_resource'];
            $resourceConfig['username'] = $this->pageData['setup_database_creation']['username'];
            $resourceConfig['password'] = $this->pageData['setup_database_creation']['password'];
            $db = new DbTool($resourceConfig);

            try {
                $db->connectToDb();
                $message = sprintf(
                    t(
                        'The database user "%s" will be used to setup the missing'
                        . ' schema required by Icinga Web 2 in database "%s".'
                    ),
                    $this->pageData['setup_database_creation']['username'],
                    $resourceConfig['dbname']
                );
            } catch (PDOException $e) {
                $message = sprintf(
                    t(
                        'The database user "%s" will be used to create the missing database "%s" '
                        . 'with the schema required by Icinga Web 2 and a new login called "%s".'
                    ),
                    $this->pageData['setup_database_creation']['username'],
                    $resourceConfig['dbname'],
                    $this->pageData['setup_db_resource']['username']
                );
            }

            $summary[t('Database Setup')] = $message;
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
