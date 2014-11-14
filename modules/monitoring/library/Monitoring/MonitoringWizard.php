<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring;

use Icinga\Web\Form;
use Icinga\Web\Wizard;
use Icinga\Web\Request;
use Icinga\Module\Setup\Setup;
use Icinga\Module\Setup\SetupWizard;
use Icinga\Module\Setup\Requirements;
use Icinga\Module\Setup\Utils\MakeDirStep;
use Icinga\Module\Setup\Utils\EnableModuleStep;
use Icinga\Module\Setup\Forms\SummaryPage;
use Icinga\Module\Monitoring\Forms\Setup\WelcomePage;
use Icinga\Module\Monitoring\Forms\Setup\BackendPage;
use Icinga\Module\Monitoring\Forms\Setup\InstancePage;
use Icinga\Module\Monitoring\Forms\Setup\SecurityPage;
use Icinga\Module\Monitoring\Forms\Setup\IdoResourcePage;
use Icinga\Module\Monitoring\Forms\Setup\LivestatusResourcePage;

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
        $this->addPage(new SummaryPage());
    }

    /**
     * @see Wizard::setupPage()
     */
    public function setupPage(Form $page, Request $request)
    {
        if ($page->getName() === 'setup_requirements') {
            $page->setRequirements($this->getRequirements());
        } elseif ($page->getName() === 'setup_summary') {
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

        if ($skip) {
            if ($this->hasPageData($newPage->getName())) {
                $pageData = & $this->getPageData();
                unset($pageData[$newPage->getName()]);
            }

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

        $setup->addStep(new MakeDirStep(array($this->getConfigDir() . '/modules/monitoring'), 0775));

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

        $setup->addStep(new EnableModuleStep('monitoring'));

        return $setup;
    }

    /**
     * @see SetupWizard::getRequirements()
     */
    public function getRequirements()
    {
        return new Requirements();
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
