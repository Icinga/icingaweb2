<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup;

use Icinga\Application\Platform;
use Icinga\Module\Setup\Requirement\SetRequirement;
use Icinga\Module\Setup\Requirement\WebLibraryRequirement;
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
use Icinga\Module\Setup\Forms\AuthBackendPage;
use Icinga\Module\Setup\Forms\AdminAccountPage;
use Icinga\Module\Setup\Forms\LdapDiscoveryPage;
//use Icinga\Module\Setup\Forms\LdapDiscoveryConfirmPage;
use Icinga\Module\Setup\Forms\LdapResourcePage;
use Icinga\Module\Setup\Forms\RequirementsPage;
use Icinga\Module\Setup\Forms\GeneralConfigPage;
use Icinga\Module\Setup\Forms\AuthenticationPage;
use Icinga\Module\Setup\Forms\DatabaseCreationPage;
use Icinga\Module\Setup\Forms\UserGroupBackendPage;
use Icinga\Module\Setup\Steps\DatabaseStep;
use Icinga\Module\Setup\Steps\GeneralConfigStep;
use Icinga\Module\Setup\Steps\ResourceStep;
use Icinga\Module\Setup\Steps\AuthenticationStep;
use Icinga\Module\Setup\Steps\UserGroupStep;
use Icinga\Module\Setup\Utils\EnableModuleStep;
use Icinga\Module\Setup\Utils\DbTool;
use Icinga\Module\Setup\Requirement\OSRequirement;
use Icinga\Module\Setup\Requirement\PhpModuleRequirement;
use Icinga\Module\Setup\Requirement\PhpVersionRequirement;
use Icinga\Module\Setup\Requirement\ConfigDirectoryRequirement;
use Icinga\Module\Monitoring\Forms\Config\Transport\ApiTransportForm;

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
        'icingaweb_user_preference',
        'icingaweb_rememberme'
    );

    /**
     * Register all pages and module wizards for this wizard
     */
    protected function init()
    {
        $this->addPage(new WelcomePage());
        $this->addPage(new ModulePage());
        $this->addPage(new RequirementsPage());
        $this->addPage(new AuthenticationPage());
        $this->addPage(new DbResourcePage(array('name' => 'setup_auth_db_resource')));
        $this->addPage(new DatabaseCreationPage(array('name' => 'setup_auth_db_creation')));
        $this->addPage(new LdapDiscoveryPage());
        //$this->addPage(new LdapDiscoveryConfirmPage());
        $this->addPage(new LdapResourcePage());
        $this->addPage(new AuthBackendPage());
        $this->addPage(new UserGroupBackendPage());
        $this->addPage(new AdminAccountPage());
        $this->addPage(new GeneralConfigPage());
        $this->addPage(new DbResourcePage(array('name' => 'setup_config_db_resource')));
        $this->addPage(new DatabaseCreationPage(array('name' => 'setup_config_db_creation')));
        $this->addPage(new SummaryPage(array('name' => 'setup_summary')));

        if (($modulePageData = $this->getPageData('setup_modules')) !== null) {
            /** @var ModulePage $modulePage */
            $modulePage = $this->getPage('setup_modules')->populate($modulePageData);
            foreach ($modulePage->getModuleWizards() as $moduleWizard) {
                $this->addPage($moduleWizard);
            }
        }
    }

    /**
     * Setup the given page that is either going to be displayed or validated
     *
     * @param   Form        $page       The page to setup
     * @param   Request     $request    The current request
     */
    public function setupPage(Form $page, Request $request)
    {
        if ($page->getName() === 'setup_requirements') {
            /** @var RequirementsPage $page */
            $page->setWizard($this);
        } elseif ($page->getName() === 'setup_authentication_backend') {
            /** @var AuthBackendPage $page */

            $authData = $this->getPageData('setup_authentication_type');
            if ($authData['type'] === 'db') {
                $page->setResourceConfig($this->getPageData('setup_auth_db_resource'));
            } elseif ($authData['type'] === 'ldap') {
                $page->setResourceConfig($this->getPageData('setup_ldap_resource'));

                $suggestions = $this->getPageData('setup_ldap_discovery');
                if (isset($suggestions['backend'])) {
                    $page->setSuggestions($suggestions['backend']);
                }

                if ($this->getDirection() === static::FORWARD) {
                    $backendConfig = $this->getPageData('setup_authentication_backend');
                    if ($backendConfig !== null && $request->getPost('name') !== $backendConfig['name']) {
                        $pageData = & $this->getPageData();
                        unset($pageData['setup_usergroup_backend']);
                    }
                }
            }

            if ($this->getDirection() === static::FORWARD) {
                $backendConfig = $this->getPageData('setup_authentication_backend');
                if ($backendConfig !== null && $request->getPost('backend') !== $backendConfig['backend']) {
                    $pageData = & $this->getPageData();
                    unset($pageData['setup_usergroup_backend']);
                }
            }
        /*} elseif ($page->getName() === 'setup_ldap_discovery_confirm') {
            $page->setResourceConfig($this->getPageData('setup_ldap_discovery'));*/
        } elseif ($page->getName() === 'setup_auth_db_resource') {
            $page->addDescription(mt(
                'setup',
                'Now please configure the database resource where to store users and user groups.'
            ));
            $page->addDescription(mt(
                'setup',
                'Note that the database itself does not need to exist at this time as'
                . ' it is going to be created once the wizard is about to be finished.'
            ));
        } elseif ($page->getName() === 'setup_usergroup_backend') {
            /** @var UserGroupBackendPage $page */
            $page->setResourceConfig($this->getPageData('setup_ldap_resource'));
            $page->setBackendConfig($this->getPageData('setup_authentication_backend'));
        } elseif ($page->getName() === 'setup_admin_account') {
            /** @var AdminAccountPage $page */
            $page->setBackendConfig($this->getPageData('setup_authentication_backend'));
            $page->setGroupConfig($this->getPageData('setup_usergroup_backend'));
            $authData = $this->getPageData('setup_authentication_type');
            if ($authData['type'] === 'db') {
                $page->setResourceConfig($this->getPageData('setup_auth_db_resource'));
            } elseif ($authData['type'] === 'ldap') {
                $page->setResourceConfig($this->getPageData('setup_ldap_resource'));
            }
        } elseif ($page->getName() === 'setup_auth_db_creation' || $page->getName() === 'setup_config_db_creation') {
            /** @var DatabaseCreationPage $page */
            $page->setDatabaseSetupPrivileges(
                array_unique(array_merge($this->databaseCreationPrivileges, $this->databaseSetupPrivileges))
            );
            $page->setDatabaseUsagePrivileges($this->databaseUsagePrivileges);
            $page->setResourceConfig(
                $this->getPageData('setup_auth_db_resource') ?: $this->getPageData('setup_config_db_resource')
            );
        } elseif ($page->getName() === 'setup_summary') {
            /** @var SummaryPage $page */
            $page->setSubjectTitle('Icinga Web 2');
            $page->setSummary($this->getSetup()->getSummary());
        } elseif ($page->getName() === 'setup_config_db_resource') {
            $page->addDescription(mt(
                'setup',
                'Now please configure the database resource where to store user preferences.'
            ));
            $page->addDescription(mt(
                'setup',
                'Note that the database itself does not need to exist at this time as'
                . ' it is going to be created once the wizard is about to be finished.'
            ));

            $ldapData = $this->getPageData('setup_ldap_resource');
            if ($ldapData !== null && $request->getPost('name') === $ldapData['name']) {
                $page->error(
                    mt('setup', 'The given resource name must be unique and is already in use by the LDAP resource')
                );
            }
        } elseif ($page->getName() === 'setup_ldap_resource') {
            $suggestion = $this->getPageData('setup_ldap_discovery');
            if (isset($suggestion['resource'])) {
                $page->populate($suggestion['resource']);
            }

            if ($this->getDirection() === static::FORWARD) {
                $resourceConfig = $this->getPageData('setup_ldap_resource');
                if ($resourceConfig !== null && $request->getPost('name') !== $resourceConfig['name']) {
                    $pageData = & $this->getPageData();
                    unset($pageData['setup_usergroup_backend']);
                }
            }
        } elseif ($page->getName() === 'setup_authentication_type') {
            $authData = $this->getPageData($page->getName());
            $pageData = & $this->getPageData();

            if ($authData !== null && $request->getPost('type') !== $authData['type']) {
                // Drop any existing page data in case the authentication type has changed,
                // otherwise it will conflict with other forms that depend on this one
                unset($pageData['setup_admin_account']);
                unset($pageData['setup_authentication_backend']);

                if ($authData['type'] === 'db') {
                    unset($pageData['setup_auth_db_resource']);
                    unset($pageData['setup_auth_db_creation']);
                } elseif ($request->getPost('type') === 'db') {
                    unset($pageData['setup_config_db_resource']);
                    unset($pageData['setup_config_db_creation']);
                }
            } elseif (isset($authData['type']) && $authData['type'] == 'external') {
                // If you choose the authentication type external and validate the database and then come
                // back to change the authentication type but do not change it, you will get an database configuration
                // related error message on the next page. To avoid this error, the 'setup_config_db_resource'
                // page must be unset.

                unset($pageData['setup_config_db_resource']);
            }
        }
    }

    /**
     * Return the new page to set as current page
     *
     * {@inheritdoc} Runs additional checks related to some registered pages.
     *
     * @param   string  $requestedPage      The name of the requested page
     * @param   Form    $originPage         The origin page
     *
     * @return  Form                        The new page
     *
     * @throws  InvalidArgumentException    In case the requested page does not exist or is not permitted yet
     */
    protected function getNewPage($requestedPage, Form $originPage)
    {
        $skip = false;
        $newPage = parent::getNewPage($requestedPage, $originPage);
        if ($newPage->getName() === 'setup_auth_db_resource') {
            $authData = $this->getPageData('setup_authentication_type');
            $skip = $authData['type'] !== 'db';
        } elseif ($newPage->getname() === 'setup_ldap_discovery') {
            $authData = $this->getPageData('setup_authentication_type');
            $skip = $authData['type'] !== 'ldap';
        /*} elseif ($newPage->getName() === 'setup_ldap_discovery_confirm') {
            $skip = false === $this->hasPageData('setup_ldap_discovery');*/
        } elseif ($newPage->getName() === 'setup_ldap_resource') {
            $authData = $this->getPageData('setup_authentication_type');
            $skip = $authData['type'] !== 'ldap';
        } elseif ($newPage->getName() === 'setup_usergroup_backend') {
            $backendConfig = $this->getPageData('setup_authentication_backend');
            $skip = $backendConfig['backend'] !== 'ldap';
        } elseif ($newPage->getName() === 'setup_config_db_resource') {
            $authData = $this->getPageData('setup_authentication_type');
            $skip = $authData['type'] === 'db';
        } elseif (in_array($newPage->getName(), array('setup_auth_db_creation', 'setup_config_db_creation'))) {
            if (($newPage->getName() === 'setup_auth_db_creation' || $this->hasPageData('setup_config_db_resource'))
                && (($config = $this->getPageData('setup_auth_db_resource')) !== null
                    || ($config = $this->getPageData('setup_config_db_resource')) !== null)
                    && !$config['skip_validation'] &&  $this->getDirection() == static::FORWARD
            ) {
                // Execute this code only if the direction is forward.
                // Otherwise, an error will be output when you go back.
                $db = new DbTool($config);

                try {
                    $db->connectToDb(); // Are we able to login on the database?

                    if (array_search(reset($this->databaseTables), $db->listTables(), true) === false) {
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
     * Add buttons to the given page based on its position in the page-chain
     *
     * @param   Form    $page   The page to add the buttons to
     */
    protected function addButtons(Form $page)
    {
        parent::addButtons($page);

        $pages = $this->getPages();
        $index = array_search($page, $pages, true);
        if ($index === 0) {
            $page->getElement(static::BTN_NEXT)->setLabel(
                mt('setup', 'Start', 'setup.welcome.btn.next')
            );
        } elseif ($index === count($pages) - 1) {
            $page->getElement(static::BTN_NEXT)->setLabel(
                mt('setup', 'Setup Icinga Web 2', 'setup.summary.btn.finish')
            );
        }

        $authData = $this->getPageData('setup_authentication_type');
        $veto = $page->getName() === 'setup_authentication_backend' && $authData['type'] === 'db';
        if (! $veto && in_array($page->getName(), array(
            'setup_authentication_backend',
            'setup_auth_db_resource',
            'setup_config_db_resource',
            'setup_ldap_resource',
            'setup_monitoring_ido',
            'setup_icingadb_resource',
            'setup_icingadb_redis',
            'setup_icingadb_api_transport'
        ))) {
            $page->addElement(
                'submit',
                'backend_validation',
                array(
                    'ignore'                => true,
                    'label'                 => t('Validate Configuration'),
                    'data-progress-label'   => t('Validation In Progress'),
                    'decorators'            => array('ViewHelper'),
                    'formnovalidate'        => 'formnovalidate'
                )
            );
            $page->getDisplayGroup('buttons')->addElement($page->getElement('backend_validation'));
        }

        if ($page->getName() === 'setup_command_transport') {
            if ($page->getSubForm('transport_form')->getSubForm('transport_form') instanceof ApiTransportForm) {
                $page->addElement(
                    'submit',
                    'transport_validation',
                    array(
                        'ignore'                => true,
                        'label'                 => t('Validate Configuration'),
                        'data-progress-label'   => t('Validation In Progress'),
                        'decorators'            => array('ViewHelper'),
                        'formnovalidate'        => 'formnovalidate'
                    )
                );
                $page->getDisplayGroup('buttons')->addElement($page->getElement('transport_validation'));
            }
        }
    }

    /**
     * Clear the session being used by this wizard
     *
     * @param   bool    $removeToken    If true, the setup token will be removed
     */
    public function clearSession($removeToken = true)
    {
        parent::clearSession();

        if ($removeToken) {
            $tokenPath = Config::resolvePath('setup.token');
            if (file_exists($tokenPath)) {
                @unlink($tokenPath);
            }
        }
    }

    /**
     * Return the setup for this wizard
     *
     * @return  Setup
     */
    public function getSetup()
    {
        $pageData = $this->getPageData();
        $setup = new Setup();

        if (isset($pageData['setup_auth_db_resource'])
            && !$pageData['setup_auth_db_resource']['skip_validation']
            && (! isset($pageData['setup_auth_db_creation'])
                || !$pageData['setup_auth_db_creation']['skip_validation']
            )
        ) {
            $setup->addStep(
                new DatabaseStep(array(
                    'tables'            => $this->databaseTables,
                    'privileges'        => $this->databaseUsagePrivileges,
                    'resourceConfig'    => $pageData['setup_auth_db_resource'],
                    'adminName'         => isset($pageData['setup_auth_db_creation']['username'])
                        ? $pageData['setup_auth_db_creation']['username']
                        : null,
                    'adminPassword'     => isset($pageData['setup_auth_db_creation']['password'])
                        ? $pageData['setup_auth_db_creation']['password']
                        : null,
                    'schemaPath'        => Config::module('setup')
                        ->get('schema', 'path', Icinga::app()->getBaseDir('schema'))
                ))
            );
        } elseif (isset($pageData['setup_config_db_resource'])
            && !$pageData['setup_config_db_resource']['skip_validation']
            && (! isset($pageData['setup_config_db_creation'])
                || !$pageData['setup_config_db_creation']['skip_validation']
            )
        ) {
            $setup->addStep(
                new DatabaseStep(array(
                    'tables'            => $this->databaseTables,
                    'privileges'        => $this->databaseUsagePrivileges,
                    'resourceConfig'    => $pageData['setup_config_db_resource'],
                    'adminName'         => isset($pageData['setup_config_db_creation']['username'])
                        ? $pageData['setup_config_db_creation']['username']
                        : null,
                    'adminPassword'     => isset($pageData['setup_config_db_creation']['password'])
                        ? $pageData['setup_config_db_creation']['password']
                        : null,
                    'schemaPath'        => Config::module('setup')
                        ->get('schema', 'path', Icinga::app()->getBaseDir('schema'))
                ))
            );
        }

        $setup->addStep(
            new GeneralConfigStep(array(
                'generalConfig' => $pageData['setup_general_config'],
                'resourceName'  => isset($pageData['setup_auth_db_resource']['name'])
                    ? $pageData['setup_auth_db_resource']['name']
                    : (isset($pageData['setup_config_db_resource']['name'])
                        ? $pageData['setup_config_db_resource']['name']
                        : null
                    )
            ))
        );

        $adminAccountType = $pageData['setup_admin_account']['user_type'];
        if ($adminAccountType === 'user_group') {
            $adminAccountData = array('groupname' => $pageData['setup_admin_account'][$adminAccountType]);
        } else {
            $adminAccountData = array('username' => $pageData['setup_admin_account'][$adminAccountType]);
            if ($adminAccountType === 'new_user' && !$pageData['setup_auth_db_resource']['skip_validation']
                && (! isset($pageData['setup_auth_db_creation'])
                    || !$pageData['setup_auth_db_creation']['skip_validation']
                )
            ) {
                $adminAccountData['resourceConfig'] = $pageData['setup_auth_db_resource'];
                $adminAccountData['password'] = $pageData['setup_admin_account']['new_user_password'];
            }
        }
        $authType = $pageData['setup_authentication_type']['type'];
        $setup->addStep(
            new AuthenticationStep(array(
                'adminAccountData'  => $adminAccountData,
                'backendConfig'     => $pageData['setup_authentication_backend'],
                'resourceName'      => $authType === 'db' ? $pageData['setup_auth_db_resource']['name'] : (
                    $authType === 'ldap' ? $pageData['setup_ldap_resource']['name'] : null
                )
            ))
        );

        if ($authType !== 'external') {
            $setup->addStep(
                new UserGroupStep(array(
                    'backendConfig'     => $pageData['setup_authentication_backend'],
                    'groupConfig'       => isset($pageData['setup_usergroup_backend'])
                        ? $pageData['setup_usergroup_backend']
                        : null,
                    'resourceName'      => $authType === 'db'
                        ? $pageData['setup_auth_db_resource']['name']
                        : $pageData['setup_ldap_resource']['name'],
                    'resourceConfig'    => $authType === 'db'
                        ? $pageData['setup_auth_db_resource']
                        : null,
                    'username'          => $authType === 'db'
                        ? $pageData['setup_admin_account'][$adminAccountType]
                        : null
                ))
            );
        }

        if (isset($pageData['setup_auth_db_resource'])
            || isset($pageData['setup_config_db_resource'])
            || isset($pageData['setup_ldap_resource'])
        ) {
            $setup->addStep(
                new ResourceStep(array(
                    'dbResourceConfig'      => isset($pageData['setup_auth_db_resource'])
                        ? array_diff_key($pageData['setup_auth_db_resource'], array('skip_validation' => null))
                        : (isset($pageData['setup_config_db_resource'])
                            ? array_diff_key($pageData['setup_config_db_resource'], array('skip_validation' => null))
                            : null
                        ),
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

        /** @var ModulePage $setupPage */
        $setupPage = $this->getPage('setup_modules');
        $setup->addStep(new EnableModuleStep(array_keys($setupPage->getCheckedModules())));

        return $setup;
    }

    /**
     * Return the requirements of this wizard
     *
     * @return  RequirementSet
     */
    public function getRequirements($skipModules = false)
    {
        $set = new RequirementSet();

        $set->add(new PhpVersionRequirement(array(
            'condition'     => array('>=', '7.2'),
            'description'   => sprintf(mt(
                'setup',
                'Running Icinga Web 2 requires PHP version %s.'
            ), '7.2')
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

        $set->add(new WebLibraryRequirement(array(
            'condition'     => ['icinga-php-library', '>=', '0.9.0'],
            'alias'         => 'Icinga PHP library',
            'description'   => mt(
                'setup',
                'The Icinga PHP library (IPL) is required for Icinga Web 2 and modules'
            )
        )));

        $set->add(new WebLibraryRequirement(array(
            'condition'     => ['icinga-php-thirdparty', '>=', '0.11.0'],
            'alias'         => 'Icinga PHP Thirdparty',
            'description'   => mt(
                'setup',
                'The Icinga PHP Thirdparty library is required for Icinga Web 2 and modules'
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
            'condition'     => 'XML',
            'description'   => mt(
                'setup',
                'The XML module for PHP is required for Markdown and custom HTML annotations.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'condition'     => 'JSON',
            'description'   => mt(
                'setup',
                'The JSON module for PHP is required for various export functionalities as well as APIs.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'condition'     => 'gettext',
            'description'   => mt(
                'setup',
                'For message localization, the gettext module for PHP is required.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'condition'     => 'INTL',
            'description'   => mt(
                'setup',
                'For language, timezone and date/time format negotiation, the INTL module for PHP is required.'
            )
        )));

        $set->add(new PhpModuleRequirement(array(
            'condition'     => 'DOM',
            'description'   => mt(
                'setup',
                'For charts and exports of views and reports to PDF, the DOM module for PHP is required.'
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
            'condition'     => 'mbstring',
            'description'   => mt(
                'setup',
                'In case you want views being exported to PDF, you\'ll need the mbstring extension for PHP.'
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

        $dbSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $dbSet->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'pdo_mysql',
            'alias'         => 'PDO-MySQL',
            'description'   => mt(
                'setup',
                'To store users or preferences in a MySQL database the PDO-MySQL module for PHP is required.'
            )
        )));
        $dbSet->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'pdo_pgsql',
            'alias'         => 'PDO-PostgreSQL',
            'description'   => mt(
                'setup',
                'To store users or preferences in a PostgreSQL database the PDO-PostgreSQL module for PHP is required.'
            )
        )));
        $set->merge($dbSet);

        $dbRequire = (new SetRequirement(array(
            'optional'      => false,
            'condition'     => $dbSet,
            'title'         =>'Database',
            'alias'         => 'PDO-MySQL OR PDO-PostgreSQL',
            'description'   => mt(
                'setup',
                'A database is mandatory, therefore at least one module '
                . 'PDO-MySQL or PDO-PostgreSQL for PHP is required.'
            )
        )));

        $set->add($dbRequire);

        $set->add(new ConfigDirectoryRequirement(array(
            'condition'     => Icinga::app()->getStorageDir(),
            'title'         => mt('setup', 'Read- and writable storage directory'),
            'description'   => mt(
                'setup',
                'The Icinga Web 2 storage directory defaults to "/var/lib/icingaweb2", if' .
                ' not explicitly set in the environment variable "ICINGAWEB_STORAGEDIR".'
            )
        )));

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
