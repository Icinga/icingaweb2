<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Icinga\Form\Setup\WelcomePage;
use Icinga\Form\Setup\DbResourcePage;
use Icinga\Form\Setup\PreferencesPage;
use Icinga\Form\Setup\AuthBackendPage;
use Icinga\Form\Setup\AdminAccountPage;
use Icinga\Form\Setup\LdapResourcePage;
use Icinga\Form\Setup\RequirementsPage;
use Icinga\Form\Setup\GeneralConfigPage;
use Icinga\Form\Setup\AuthenticationPage;
use Icinga\Web\Form;
use Icinga\Web\Wizard;
use Icinga\Web\Request;
use Icinga\Web\Setup\SetupWizard;
use Icinga\Web\Setup\Requirements;
use Icinga\Application\Platform;

/**
 * Icinga Web 2 Setup Wizard
 */
class WebSetup extends Wizard implements SetupWizard
{
    /**
     * @see Wizard::init()
     */
    protected function init()
    {
        $this->addPage(new WelcomePage());
        $this->addPage(new RequirementsPage());
        $this->addPage(new AuthenticationPage());
        $this->addPage(new PreferencesPage());
        $this->addPage(new DbResourcePage());
        $this->addPage(new LdapResourcePage());
        $this->addPage(new AuthBackendPage());
        $this->addPage(new GeneralConfigPage());
        $this->addPage(new AdminAccountPage());
    }

    /**
     * @see Wizard::setupPage()
     */
    public function setupPage(Form $page, Request $request)
    {
        if ($page->getName() === 'setup_requirements') {
            $page->setRequirements($this->getRequirements());
        } elseif ($page->getName() === 'setup_preferences_type') {
            $authData = $this->getPageData('setup_authentication_type');
            if ($authData['type'] === 'db') {
                $page->create()->showDatabaseNote();
            }
        } elseif ($page->getName() === 'setup_authentication_backend') {
            $authData = $this->getPageData('setup_authentication_type');
            if ($authData['type'] === 'db') {
                $page->setResourceConfig($this->getPageData('setup_db_resource'));
            } elseif ($authData['type'] === 'ldap') {
                $page->setResourceConfig($this->getPageData('setup_ldap_resource'));
            }
        } elseif ($page->getName() === 'setup_admin_account') {
            $page->setBackendConfig($this->getPageData('setup_authentication_backend'));
            $authData = $this->getPageData('setup_authentication_type');
            if ($authData['type'] === 'db') {
                $page->setResourceConfig($this->getPageData('setup_db_resource'));
            } elseif ($authData['type'] === 'ldap') {
                $page->setResourceConfig($this->getPageData('setup_ldap_resource'));
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
            $skip = $prefData['type'] !== 'db' && $authData['type'] !== 'db';
        } elseif ($newPage->getName() === 'setup_ldap_resource') {
            $authData = $this->getPageData('setup_authentication_type');
            $skip = $authData['type'] !== 'ldap';
        }

        if ($skip) {
            $pages = $this->getPages();
            if ($this->getDirection() === static::FORWARD) {
                $nextPage = $pages[array_search($newPage, $pages, true) + 1];
                $newPage = $this->getNewPage($nextPage->getName(), $newPage);
            } else { // $this->getDirection() === static::BACKWARD
                $previousPage = $pages[array_search($newPage, $pages, true) - 1];
                $newPage = $this->getNewPage($previousPage->getName(), $newPage);
            }
        }

        return $newPage;
    }

    /**
     * @see SetupWizard::getInstaller()
     */
    public function getInstaller()
    {
        return new WebInstaller($this->getPageData());
    }

    /**
     * @see SetupWizard::getRequirements()
     */
    public function getRequirements()
    {
        $requirements = new Requirements();

        $phpVersion = Platform::getPhpVersion();
        $requirements->addMandatory(
            t('PHP Version'),
            t(
                'Running Icingaweb requires PHP version 5.3.2. Advanced features'
                . ' like the built-in web server require PHP version 5.4.'
            ),
            version_compare($phpVersion, '5.3.2', '>='),
            sprintf(t('You are running PHP version %s.'), $phpVersion)
        );

        $requirements->addOptional(
            t('Linux Platform'),
            t(
                'Icingaweb is developed for and tested on Linux. While we cannot'
                . ' guarantee they will, other platforms may also perform as well.'
            ),
            Platform::isLinux(),
            sprintf(t('You are running PHP on a %s system.'), Platform::getOperatingSystemName())
        );

        $requirements->addOptional(
            t('PHP Module: POSIX'),
            t(
                'It is strongly suggested to install/enable the POSIX module for PHP. While ' .
                'it is not required for the web frontend it is essential for the Icinga CLI.'
            ),
            Platform::extensionLoaded('posix'),
            Platform::extensionLoaded('posix') ? t('The PHP module POSIX is available.') : (
                t('The PHP module POSIX is missing.')
            )
        );

        $requirements->addOptional(
            t('PHP Module: JSON'),
            t('The JSON module for PHP is required for various export functionalities as well as APIs.'),
            Platform::extensionLoaded('json'),
            Platform::extensionLoaded('json') ? t('The PHP module JSON is available.') : (
                t('The PHP module JSON is missing.')
            )
        );

        $requirements->addOptional(
            t('PHP Module: LDAP'),
            t('If you\'d like to authenticate users using LDAP the corresponding PHP module is required'),
            Platform::extensionLoaded('ldap'),
            Platform::extensionLoaded('ldap') ? t('The PHP module LDAP is available') : (
                t('The PHP module LDAP is missing')
            )
        );

        $requirements->addOptional(
            t('PHP Module: PDO'),
            t(
                'Though Icingaweb can be operated without any database access, it is recommended to install/enable' .
                ' the PDO module for PHP to gain a significant performance increase as well as more flexibility.'
            ),
            Platform::extensionLoaded('pdo'),
            Platform::extensionLoaded('pdo') ? t('The PHP module PDO is available.') : (
                t('The PHP module PDO is missing.')
            )
        );

        $mysqlAdapterFound = Platform::zendClassExists('Zend_Db_Adapter_Pdo_Mysql');
        $requirements->addOptional(
            t('Zend Database Adapter For MySQL'),
            t('The Zend database adapter for MySQL is required to access a MySQL database.'),
            $mysqlAdapterFound,
            $mysqlAdapterFound ? t('The Zend database adapter for MySQL is available.') : (
                t('The Zend database adapter for MySQL is missing.')
            )
        );

        $pgsqlAdapterFound = Platform::zendClassExists('Zend_Db_Adapter_Pdo_Pgsql');
        $requirements->addOptional(
            t('Zend Database Adapter For PostgreSQL'),
            t('The Zend database adapter for PostgreSQL is required to access a PostgreSQL database.'),
            $pgsqlAdapterFound,
            $pgsqlAdapterFound ? t('The Zend database adapter for PostgreSQL is available.') : (
                t('The Zend database adapter for PostgreSQL is missing.')
            )

        );

        $defaultTimezone = Platform::getPhpConfig('date.timezone');
        $requirements->addMandatory(
            t('Default Timezone'),
            t('It is required that a default timezone has been set using date.timezone in php.ini.'),
            $defaultTimezone,
            $defaultTimezone ? sprintf(t('Your default timezone is: %s'), $defaultTimezone) : (
                t('You did not define a default timezone.')
            )
        );

        $configDir = $this->getConfigDir();
        $requirements->addMandatory(
            t('Writable Config Directory'),
            t(
                'The Icingaweb configuration directory defaults to "/etc/icingaweb", if' .
                ' not explicitly set in the environment variable "ICINGAWEB_CONFIGDIR".'
            ),
            is_writable($configDir),
            sprintf(
                is_writable($configDir) ? t('The current configuration directory is writable: %s') : (
                    t('The current configuration directory is not writable: %s')
                ),
                $configDir
            )
        );

        return $requirements;
    }

    /**
     * Return the configuration directory of Icinga Web 2
     *
     * @return  string
     */
    protected function getConfigDir()
    {
        if (array_key_exists('ICINGAWEB_CONFIGDIR', $_SERVER)) {
            $configDir = $_SERVER['ICINGAWEB_CONFIGDIR'];
        } else {
            $configDir = '/etc/icingaweb';
        }

        $canonical = realpath($configDir);
        return $canonical ? $canonical : $configDir;
    }
}
