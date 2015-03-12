<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring;

use Icinga\Web\Form;
use Icinga\Web\Wizard;
use Icinga\Web\Request;
use Icinga\Module\Setup\Setup;
use Icinga\Module\Setup\SetupWizard;
use Icinga\Module\Setup\RequirementSet;
use Icinga\Module\Setup\Forms\SummaryPage;
use Icinga\Module\Monitoring\Forms\Setup\WelcomePage;
use Icinga\Module\Monitoring\Forms\Setup\BackendPage;
use Icinga\Module\Monitoring\Forms\Setup\InstancePage;
use Icinga\Module\Monitoring\Forms\Setup\SecurityPage;
use Icinga\Module\Monitoring\Forms\Setup\IdoResourcePage;
use Icinga\Module\Monitoring\Forms\Setup\LivestatusResourcePage;
use Icinga\Module\Setup\Requirement\ClassRequirement;
use Icinga\Module\Setup\Requirement\PhpModuleRequirement;

/**
 * Monitoring Module Setup Wizard
 */
class MonitoringWizard extends Wizard implements SetupWizard
{
    /**
     * @see Wizard::init()
     */
    public function init()
    {
        $this->addPage(new WelcomePage());
        $this->addPage(new BackendPage());
        $this->addPage(new IdoResourcePage());
        $this->addPage(new LivestatusResourcePage());
        $this->addPage(new InstancePage());
        $this->addPage(new SecurityPage());
        $this->addPage(new SummaryPage(array('name' => 'setup_monitoring_summary')));
    }

    /**
     * @see Wizard::setupPage()
     */
    public function setupPage(Form $page, Request $request)
    {
        if ($page->getName() === 'setup_requirements') {
            $page->setRequirements($this->getRequirements());
        } elseif ($page->getName() === 'setup_monitoring_summary') {
            $page->setSummary($this->getSetup()->getSummary());
            $page->setSubjectTitle(mt('monitoring', 'the monitoring module', 'setup.summary.subject'));
        } elseif (
            $this->getDirection() === static::FORWARD
            && ($page->getName() === 'setup_monitoring_ido' || $page->getName() === 'setup_monitoring_livestatus')
        ) {
            if ((($dbResourceData = $this->getPageData('setup_db_resource')) !== null
                 && $dbResourceData['name'] === $request->getPost('name'))
                || (($ldapResourceData = $this->getPageData('setup_ldap_resource')) !== null
                    && $ldapResourceData['name'] === $request->getPost('name'))
            ) {
                $page->addError(mt('monitoring', 'The given resource name is already in use.'));
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
        if ($newPage->getName() === 'setup_monitoring_ido') {
            $backendData = $this->getPageData('setup_monitoring_backend');
            $skip = $backendData['type'] !== 'ido';
        } elseif ($newPage->getName() === 'setup_monitoring_livestatus') {
            $backendData = $this->getPageData('setup_monitoring_backend');
            $skip = $backendData['type'] !== 'livestatus';
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
            // Used t() here as "Start" is too generic and already translated in the icinga domain
            $page->getElement(static::BTN_NEXT)->setLabel(t('Start', 'setup.welcome.btn.next'));
        } elseif ($index === count($pages) - 1) {
            $page->getElement(static::BTN_NEXT)->setLabel(
                mt('monitoring', 'Setup the monitoring module for Icinga Web 2', 'setup.summary.btn.finish')
            );
        }
    }

    /**
     * @see SetupWizard::getSetup()
     */
    public function getSetup()
    {
        $pageData = $this->getPageData();
        $setup = new Setup();

        $setup->addStep(
            new BackendStep(array(
                'backendConfig'     => $pageData['setup_monitoring_backend'],
                'resourceConfig'    => isset($pageData['setup_monitoring_ido'])
                    ? array_diff_key($pageData['setup_monitoring_ido'], array('skip_validation' => null))
                    : array_diff_key($pageData['setup_monitoring_livestatus'], array('skip_validation' => null))
            ))
        );

        $setup->addStep(
            new InstanceStep(array(
                'instanceConfig'    => $pageData['setup_monitoring_instance']
            ))
        );

        $setup->addStep(
            new SecurityStep(array(
                'securityConfig'    => $pageData['setup_monitoring_security']
            ))
        );

        return $setup;
    }

    /**
     * @see SetupWizard::getRequirements()
     */
    public function getRequirements()
    {
        $set = new RequirementSet();

        // TODO(8254): Add this to the $backendSet
        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'Sockets',
            'description'   => mt(
                'monitoring',
                'In case it\'s desired that a TCP connection is being used by Icinga Web 2 to'
                . ' access a Livestatus interface, the Sockets module for PHP is required.'
            )
        )));

        $backendSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $mysqlSet = new RequirementSet(true);
        $mysqlSet->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'mysql',
            'alias'         => 'PDO-MySQL',
            'description'   => mt(
                'monitoring',
                'To access the IDO stored in a MySQL database the PDO-MySQL module for PHP is required.'
            )
        )));
        $mysqlSet->add(new ClassRequirement(array(
            'optional'      => true,
            'condition'     => 'Zend_Db_Adapter_Pdo_Mysql',
            'alias'         => mt('monitoring', 'Zend database adapter for MySQL'),
            'description'   => mt(
                'monitoring',
                'The Zend database adapter for MySQL is required to access a MySQL database.'
            )
        )));
        $backendSet->merge($mysqlSet);
        $pgsqlSet = new RequirementSet(true);
        $pgsqlSet->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'pgsql',
            'alias'         => 'PDO-PostgreSQL',
            'description'   => mt(
                'monitoring',
                'To access the IDO stored in a PostgreSQL database the PDO-PostgreSQL module for PHP is required.'
            )
        )));
        $pgsqlSet->add(new ClassRequirement(array(
            'optional'      => true,
            'condition'     => 'Zend_Db_Adapter_Pdo_Pgsql',
            'alias'         => mt('monitoring', 'Zend database adapter for PostgreSQL'),
            'description'   => mt(
                'monitoring',
                'The Zend database adapter for PostgreSQL is required to access a PostgreSQL database.'
            )
        )));
        $backendSet->merge($pgsqlSet);
        $set->merge($backendSet);

        return $set;
    }
}
