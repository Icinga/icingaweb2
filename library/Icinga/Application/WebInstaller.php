<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use PDOException;
use Icinga\Web\Setup\DbTool;
use Icinga\Application\Config;
use Icinga\Web\Setup\Installer;

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
     * Create a new web installer
     *
     * @param   array   $pageData   The setup wizard's page data
     */
    public function __construct(array $pageData)
    {
        $this->pageData = $pageData;
    }

    /**
     * @see Installer::run()
     */
    public function run()
    {
        return true;
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
                    t('The database user "%s" will be used to setup the missing schema required by Icinga Web 2 in database "%s".'),
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
                    $this->pageData['setup_db_resource']['dbname']
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
        return array();
    }
}
