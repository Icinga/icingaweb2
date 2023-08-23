<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring;

use Icinga\Module\Setup\Forms\RequirementsPage;
use Icinga\Web\Form;
use Icinga\Web\Wizard;
use Icinga\Web\Request;
use Icinga\Module\Setup\Setup;
use Icinga\Module\Setup\SetupWizard;
use Icinga\Module\Setup\RequirementSet;
use Icinga\Module\Setup\Forms\SummaryPage;
use Icinga\Module\Monitoring\Forms\Setup\WelcomePage;
use Icinga\Module\Monitoring\Forms\Setup\SecurityPage;
use Icinga\Module\Monitoring\Forms\Setup\TransportPage;
use Icinga\Module\Monitoring\Forms\Setup\IdoResourcePage;
use Icinga\Module\Setup\Requirement\PhpModuleRequirement;

/**
 * Monitoring Module Setup Wizard
 */
class MonitoringWizard extends Wizard implements SetupWizard
{
    /**
     * Register all pages for this wizard
     */
    public function init()
    {
        $this->addPage(new WelcomePage());
        $this->addPage(new IdoResourcePage());
        $this->addPage(new TransportPage());
        $this->addPage(new SecurityPage());
        $this->addPage(new SummaryPage(array('name' => 'setup_monitoring_summary')));
    }

    /**
     * Setup the given page that is either going to be displayed or validated
     *
     * @param   Form|RequirementsPage|SummaryPage       $page       The page to setup
     * @param   Request                                 $request    The current request
     */
    public function setupPage(Form $page, Request $request)
    {
        if ($page->getName() === 'setup_requirements') {
            $page->setRequirements($this->getRequirements());
        } elseif ($page->getName() === 'setup_monitoring_summary') {
            $page->setSummary($this->getSetup()->getSummary());
            $page->setSubjectTitle(mt('monitoring', 'the monitoring module', 'setup.summary.subject'));
        } elseif ($this->getDirection() === static::FORWARD
            && ($page->getName() === 'setup_monitoring_ido')
        ) {
            if ((($authDbResourceData = $this->getPageData('setup_auth_db_resource')) !== null
                 && $authDbResourceData['name'] === $request->getPost('name'))
                || (($configDbResourceData = $this->getPageData('setup_config_db_resource')) !== null
                    && $configDbResourceData['name'] === $request->getPost('name'))
                    || (($ldapResourceData = $this->getPageData('setup_ldap_resource')) !== null
                        && $ldapResourceData['name'] === $request->getPost('name'))
            ) {
                $page->error(mt('monitoring', 'The given resource name is already in use.'));
            }
        }
    }

    /**
     * Add buttons to the given page based on its position in the page-chain
     *
     * @param   Form    $page   The page to add the buttons to
     *
     * @todo This is never called, because its a sub-wizard only
     * @todo This is missing the ´transport_validation´ case
     * @see WebWizard::addButtons which does some of the needed work
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

        if ($page->getName() === 'setup_monitoring_ido') {
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

        $setup->addStep(
            new BackendStep(array(
                'backendConfig'     => ['name' => 'icinga', 'type' => 'ido'],
                'resourceConfig'    => array_diff_key(
                    $pageData['setup_monitoring_ido'], //TODO: Prefer a new backend once implemented.
                    array('skip_validation' => null)
                )
            ))
        );

        $setup->addStep(
            new TransportStep(array(
                'transportConfig'    => $pageData['setup_command_transport']
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
     * Return the requirements of this wizard
     *
     * @return  RequirementSet
     */
    public function getRequirements()
    {
        $set = new RequirementSet();
        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'curl',
            'alias'         => 'cURL',
            'description'   => mt(
                'monitoring',
                'To send external commands over Icinga 2\'s API the cURL module for PHP is required.'
            )
        )));

        return $set;
    }
}
