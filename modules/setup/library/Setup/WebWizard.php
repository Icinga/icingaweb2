<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup;

use PDOException;
use Icinga\Web\Form;
use Icinga\Web\Wizard;
use Icinga\Web\Request;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Module\Setup\Forms\ModulePage;
use Icinga\Module\Setup\Forms\WelcomePage;
use Icinga\Module\Setup\Forms\SummaryPage;
use Icinga\Module\Setup\Forms\DbResourcePage;
use Icinga\Module\Setup\Forms\PreferencesPage;
use Icinga\Module\Setup\Forms\AuthBackendPage;
use Icinga\Module\Setup\Forms\AdminAccountPage;
use Icinga\Module\Setup\Forms\LdapDiscoveryPage;
//use Icinga\Module\Setup\Forms\LdapDiscoveryConfirmPage;
use Icinga\Module\Setup\Forms\LdapResourcePage;
use Icinga\Module\Setup\Forms\RequirementsPage;
use Icinga\Module\Setup\Forms\GeneralConfigPage;
use Icinga\Module\Setup\Forms\AuthenticationPage;
use Icinga\Module\Setup\Forms\DatabaseCreationPage;
use Icinga\Module\Setup\Steps\DatabaseStep;
use Icinga\Module\Setup\Steps\GeneralConfigStep;
use Icinga\Module\Setup\Steps\ResourceStep;
use Icinga\Module\Setup\Steps\AuthenticationStep;
use Icinga\Module\Setup\Utils\EnableModuleStep;
use Icinga\Module\Setup\Utils\DbTool;
use Icinga\Module\Setup\Requirement\OSRequirement;
use Icinga\Module\Setup\Requirement\ClassRequirement;
use Icinga\Module\Setup\Requirement\PhpConfigRequirement;
use Icinga\Module\Setup\Requirement\PhpModuleRequirement;
use Icinga\Module\Setup\Requirement\PhpVersionRequirement;
use Icinga\Module\Setup\Requirement\ConfigDirectoryRequirement;

/**
 * Icinga Web 2 Setup Wizard
 */
class WebWizard extends Wizard implements SetupWizard
{
    /**
     * The privileges required by Icinga Web 2 to create the database and a login
     *
     * @var array
     */
    protected $databaseCreationPrivileges = array(
        'CREATE',
        'CREATE USER', // MySQL
        'CREATEROLE' // PostgreSQL
    );

    /**
     * The privileges required by Icinga Web 2 to setup the database
     *
     * @var array
     */
    protected $databaseSetupPrivileges = array(
        'CREATE',
        'ALTER', // MySQL only
        'REFERENCES'
    );

    /**
     * The privileges required by Icinga Web 2 to operate the database
     *
     * @var array
     */
    protected $databaseUsagePrivileges = array(
        'SELECT',
        'INSERT',
        'UPDATE',
        'DELETE',
        'EXECUTE',
        'TEMPORARY', // PostgreSql
        'CREATE TEMPORARY TABLES' // MySQL
    );

    /**
     * The database tables operated by Icinga Web 2
     *
     * @var array
     */
    protected $databaseTables = array(
        'icingaweb_group',
        'icingaweb_group_membership',
        'icingaweb_user',
        'icingaweb_user_preference'
    );

    /**
     * @see Wizard::init()
     */
    protected function init()
    {
        $this->addPage(new WelcomePage());
        $this->addPage(new ModulePage());
        $this->addPage(new RequirementsPage());
        $this->addPage(new AuthenticationPage());
        $this->addPage(new PreferencesPage());
        $this->addPage(new DbResourcePage());
        $this->addPage(new LdapDiscoveryPage());
        //$this->addPage(new LdapDiscoveryConfirmPage());
        $this->addPage(new LdapResourcePage());
        $this->addPage(new AuthBackendPage());
        $this->addPage(new AdminAccountPage());
        $this->addPage(new GeneralConfigPage());
        $this->addPage(new DatabaseCreationPage());
        $this->addPage(new SummaryPage(array('name' => 'setup_summary')));

        if (($modulePageData = $this->getPageData('setup_modules')) !== null) {
            $modulePage = $this->getPage('setup_modules')->populate($modulePageData);
            foreach ($modulePage->getModuleWizards() as $moduleWizard) {
                $this->addPage($moduleWizard);
            }
        }
    }

    /**
     * @see Wizard::setupPage()
     */
    public function setupPage(Form $page, Request $request)
    {
        if ($page->getName() === 'setup_requirements') {
            $page->setWizard($this);
        } elseif ($page->getName() === 'setup_preferences_type') {
            $authData = $this->getPageData('setup_authentication_type');
            if ($authData['type'] === 'db') {
                $page->create()->getElement('store')->setValue('db');
                $page->addDescription(mt(
                    'setup',
                    'Note that choosing "Database" causes Icinga Web 2 to use the same database as for authentication.'
                ));
            }
        } elseif ($page->getName() === 'setup_authentication_backend') {
            $authData = $this->getPageData('setup_authentication_type');
            if ($authData['type'] === 'db') {
                $page->setResourceConfig($this->getPageData('setup_db_resource'));
            } elseif ($authData['type'] === 'ldap') {
                $page->setResourceConfig($this->getPageData('setup_ldap_resource'));

                $suggestions = $this->getPageData('setup_ldap_discovery');
                if (isset($suggestions['backend'])) {
                    $page->populate($suggestions['backend']);
                }
            }
        /*} elseif ($page->getName() === 'setup_ldap_discovery_confirm') {
            $page->setResourceConfig($this->getPageData('setup_ldap_discovery'));*/
        } elseif ($page->getName() === 'setup_admin_account') {
            $page->setBackendConfig($this->getPageData('setup_authentication_backend'));
            $authData = $this->getPageData('setup_authentication_type');
            if ($authData['type'] === 'db') {
                $page->setResourceConfig($this->getPageData('setup_db_resource'));
            } elseif ($authData['type'] === 'ldap') {
                $page->setResourceConfig($this->getPageData('setup_ldap_resource'));
            }
        } elseif ($page->getName() === 'setup_database_creation') {
            $page->setDatabaseSetupPrivileges(
                array_unique(array_merge($this->databaseCreationPrivileges, $this->databaseSetupPrivileges))
            );
            $page->setDatabaseUsagePrivileges($this->databaseUsagePrivileges);
            $page->setResourceConfig($this->getPageData('setup_db_resource'));
        } elseif ($page->getName() === 'setup_summary') {
            $page->setSubjectTitle('Icinga Web 2');
            $page->setSummary($this->getSetup()->getSummary());
        } elseif ($page->getName() === 'setup_db_resource') {
            $ldapData = $this->getPageData('setup_ldap_resource');
            if ($ldapData !== null && $request->getPost('name') === $ldapData['name']) {
                $page->addError(
                    mt('setup', 'The given resource name must be unique and is already in use by the LDAP resource')
                );
            }
        } elseif ($page->getName() === 'setup_ldap_resource') {
            $dbData = $this->getPageData('setup_db_resource');
            if ($dbData !== null && $request->getPost('name') === $dbData['name']) {
                $page->addError(
                    mt('setup', 'The given resource name must be unique and is already in use by the database resource')
                );
            }

            $suggestion = $this->getPageData('setup_ldap_discovery');
            if (isset($suggestion['resource'])) {
                $page->populate($suggestion['resource']);
            }
        } elseif ($page->getName() === 'setup_authentication_type' && $this->getDirection() === static::FORWARD) {
            $authData = $this->getPageData($page->getName());
            if ($authData !== null && $request->getPost('type') !== $authData['type']) {
                // Drop any existing page data in case the authentication type has changed,
                // otherwise it will conflict with other forms that depend on this one
                $pageData = & $this->getPageData();
                unset($pageData['setup_admin_account']);
                unset($pageData['setup_authentication_backend']);
            }
        }
    }

    /**
     * @see Wizard::getNewPage()
     */
    protected function getNewPage($requestedPage, Form $originPage)
    {
        $skip = false;
        $newPage = parent::getNewPage($requestedPage, $originPage);
        if ($newPage->getName() === 'setup_db_resource') {
            $prefData = $this->getPageData('setup_preferences_type');
            $authData = $this->getPageData('setup_authentication_type');
            $skip = $prefData['store'] !== 'db' && $authData['type'] !== 'db';
        } elseif ($newPage->getname() === 'setup_ldap_discovery') {
            $authData = $this->getPageData('setup_authentication_type');
            $skip = $authData['type'] !== 'ldap';
        /*} elseif ($newPage->getName() === 'setup_ldap_discovery_confirm') {
            $skip = false === $this->hasPageData('setup_ldap_discovery');*/
        } elseif ($newPage->getName() === 'setup_ldap_resource') {
            $authData = $this->getPageData('setup_authentication_type');
            $skip = $authData['type'] !== 'ldap';
        } elseif ($newPage->getName() === 'setup_database_creation') {
            if (($config = $this->getPageData('setup_db_resource')) !== null && ! $config['skip_validation']) {
                $db = new DbTool($config);

                try {
                    $db->connectToDb(); // Are we able to login on the database?
                    if (array_search(key($this->databaseTables), $db->listTables()) === false) {
                        // In case the database schema does not yet exist the
                        // user needs the privileges to setup the database
                        $skip = $db->checkPrivileges($this->databaseSetupPrivileges, $this->databaseTables);
                    } else {
                        // In case the database schema exists the user needs the required privileges
                        // to operate the database, if those are missing we ask for another user
                        $skip = $db->checkPrivileges($this->databaseUsagePrivileges, $this->databaseTables);
                    }
                } catch (PDOException $_) {
                    try {
                        $db->connectToHost(); // Are we able to login on the server?
                        // It is not possible to reliably determine whether a database exists or not if a user can't
                        // log in to the database, so we just require the user to be able to create the database
                        $skip = $db->checkPrivileges(
                            array_unique(
                                array_merge($this->databaseCreationPrivileges, $this->databaseSetupPrivileges)
                            ),
                            $this->databaseTables
                        );
                    } catch (PDOException $_) {
                        // We are NOT able to login on the server..
                    }
                }
            } else {
                $skip = true;
            }
        }

        return $skip ? $this->skipPage($newPage) : $newPage;
    }

    /**
     * @see Wizard::addButtons()
     */
    protected function addButtons(Form $page)
    {
        parent::addButtons($page);

        $pages = $this->getPages();
        $index = array_search($page, $pages, true);
        if ($index === 0) {
            $page->getElement(static::BTN_NEXT)->setLabel(mt('setup', 'Start', 'setup.welcome.btn.next'));
        } elseif ($index === count($pages) - 1) {
            $page->getElement(static::BTN_NEXT)->setLabel(mt('setup', 'Setup Icinga Web 2', 'setup.summary.btn.finish'));
        }
    }

    /**
     * @see Wizard::clearSession()
     */
    public function clearSession()
    {
        parent::clearSession();

        $tokenPath = Config::resolvePath('setup.token');
        if (file_exists($tokenPath)) {
            @unlink($tokenPath);
        }
    }

    /**
     * @see SetupWizard::getSetup()
     */
    public function getSetup()
    {
        $pageData = $this->getPageData();
        $setup = new Setup();

        if (isset($pageData['setup_db_resource'])
            && ! $pageData['setup_db_resource']['skip_validation']
            && (false === isset($pageData['setup_database_creation'])
                || ! $pageData['setup_database_creation']['skip_validation']
            )
        ) {
            $setup->addStep(
                new DatabaseStep(array(
                    'tables'            => $this->databaseTables,
                    'privileges'        => $this->databaseUsagePrivileges,
                    'resourceConfig'    => $pageData['setup_db_resource'],
                    'adminName'         => isset($pageData['setup_database_creation']['username'])
                        ? $pageData['setup_database_creation']['username']
                        : null,
                    'adminPassword'     => isset($pageData['setup_database_creation']['password'])
                        ? $pageData['setup_database_creation']['password']
                        : null,
                    'schemaPath'        => Config::module('setup')
                        ->get('schema', 'path', Icinga::app()->getBaseDir('etc' . DIRECTORY_SEPARATOR . 'schema'))
                ))
            );
        }

        $setup->addStep(
            new GeneralConfigStep(array(
                'generalConfig'         => $pageData['setup_general_config'],
                'preferencesStore'       => $pageData['setup_preferences_type']['store'],
                'preferencesResource'   => isset($pageData['setup_db_resource']['name'])
                    ? $pageData['setup_db_resource']['name']
                    : null
            ))
        );

        $adminAccountType = $pageData['setup_admin_account']['user_type'];
        $adminAccountData = array('username' => $pageData['setup_admin_account'][$adminAccountType]);
        if ($adminAccountType === 'new_user' && ! $pageData['setup_db_resource']['skip_validation']
            && (false === isset($pageData['setup_database_creation'])
                || ! $pageData['setup_database_creation']['skip_validation']
            )
        ) {
            $adminAccountData['resourceConfig'] = $pageData['setup_db_resource'];
            $adminAccountData['password'] = $pageData['setup_admin_account']['new_user_password'];
        }
        $authType = $pageData['setup_authentication_type']['type'];
        $setup->addStep(
            new AuthenticationStep(array(
                'adminAccountData'  => $adminAccountData,
                'backendConfig'     => $pageData['setup_authentication_backend'],
                'resourceName'      => $authType === 'db' ? $pageData['setup_db_resource']['name'] : (
                    $authType === 'ldap' ? $pageData['setup_ldap_resource']['name'] : null
                )
            ))
        );

        if (isset($pageData['setup_db_resource']) || isset($pageData['setup_ldap_resource'])) {
            $setup->addStep(
                new ResourceStep(array(
                    'dbResourceConfig'      => isset($pageData['setup_db_resource'])
                        ? array_diff_key($pageData['setup_db_resource'], array('skip_validation' => null))
                        : null,
                    'ldapResourceConfig'    => isset($pageData['setup_ldap_resource'])
                        ? array_diff_key($pageData['setup_ldap_resource'], array('skip_validation' => null))
                        : null
                ))
            );
        }

        foreach ($this->getWizards() as $wizard) {
            if ($wizard->isComplete()) {
                $setup->addSteps($wizard->getSetup()->getSteps());
            }
        }

        $setup->addStep(new EnableModuleStep(array_keys($this->getPage('setup_modules')->getCheckedModules())));

        return $setup;
    }

    /**
     * @see SetupWizard::getRequirements()
     */
    public function getRequirements($skipModules = false)
    {
        $set = new RequirementSet();

        $set->add(new PhpVersionRequirement(array(
            'condition'     => array('>=', '5.3.2'),
            'description'   => mt(
                'setup',
                'Running Icinga Web 2 requires PHP version 5.3.2. Advanced features'
                . ' like the built-in web server require PHP version 5.4.'
            )
        )));

        $set->add(new PhpConfigRequirement(array(
            'condition'     => array('date.timezone', true),
            'title'         => mt('setup', 'Default Timezone'),
            'description'   => sprintf(
                mt('setup', 'It is required that a default timezone has been set using date.timezone in %s.'),
                php_ini_loaded_file() ?: 'php.ini'
            ),
        )));

        $set->add(new OSRequirement(array(
            'optional'      => true,
            'condition'     => 'linux',
            'description'   => mt(
                'setup',
                'Icinga Web 2 is developed for and tested on Linux. While we cannot'
                . ' guarantee they will, other platforms may also perform as well.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'condition'     => 'OpenSSL',
            'description'   => mt(
                'setup',
                'The PHP module for OpenSSL is required to generate cryptographically safe password salts.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'JSON',
            'description'   => mt(
                'setup',
                'The JSON module for PHP is required for various export functionalities as well as APIs.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'LDAP',
            'description'   => mt(
                'setup',
                'If you\'d like to authenticate users using LDAP the corresponding PHP module is required.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'INTL',
            'description'   => mt(
                'setup',
                'If you want your users to benefit from language, timezone and date/time'
                . ' format negotiation, the INTL module for PHP is required.'
            )
        )));

        // TODO(6172): Remove this requirement once we do not ship dompdf with Icinga Web 2 anymore
        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'DOM',
            'description'   => mt(
                'setup',
                'To be able to export views and reports to PDF, the DOM module for PHP is required.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'GD',
            'description'   => mt(
                'setup',
                'In case you want views being exported to PDF, you\'ll need the GD extension for PHP.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'Imagick',
            'description'   => mt(
                'setup',
                'In case you want graphs being exported to PDF as well, you\'ll need the ImageMagick extension for PHP.'
            )
        )));

        $mysqlSet = new RequirementSet(true);
        $mysqlSet->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'mysql',
            'alias'         => 'PDO-MySQL',
            'description'   => mt(
                'setup',
                'To store users or preferences in a MySQL database the PDO-MySQL module for PHP is required.'
            )
        )));
        $mysqlSet->add(new ClassRequirement(array(
            'optional'      => true,
            'condition'     => 'Zend_Db_Adapter_Pdo_Mysql',
            'alias'         => mt('setup', 'Zend database adapter for MySQL'),
            'description'   => mt(
                'setup',
                'The Zend database adapter for MySQL is required to access a MySQL database.'
            )
        )));
        $set->merge($mysqlSet);

        $pgsqlSet = new RequirementSet(true);
        $pgsqlSet->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'pgsql',
            'alias'         => 'PDO-PostgreSQL',
            'description'   => mt(
                'setup',
                'To store users or preferences in a PostgreSQL database the PDO-PostgreSQL module for PHP is required.'
            )
        )));
        $pgsqlSet->add(new ClassRequirement(array(
            'optional'      => true,
            'condition'     => 'Zend_Db_Adapter_Pdo_Pgsql',
            'alias'         => mt('setup', 'Zend database adapter for PostgreSQL'),
            'description'   => mt(
                'setup',
                'The Zend database adapter for PostgreSQL is required to access a PostgreSQL database.'
            )
        )));
        $set->merge($pgsqlSet);

        $set->add(new ConfigDirectoryRequirement(array(
            'condition'     => Icinga::app()->getConfigDir(),
            'description'   => mt(
                'setup',
                'The Icinga Web 2 configuration directory defaults to "/etc/icingaweb2", if' .
                ' not explicitly set in the environment variable "ICINGAWEB_CONFIGDIR".'
            )
        )));

        if (! $skipModules) {
            foreach ($this->getWizards() as $wizard) {
                $set->merge($wizard->getRequirements());
            }
        }

        return $set;
    }
}
