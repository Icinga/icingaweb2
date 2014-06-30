<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\Session;
use Icinga\Web\Wizard\Wizard;
use Icinga\Web\Controller\ActionController;

class InstallController extends ActionController
{
    /**
     * Whether the controller requires the user to be authenticated
     *
     * The install wizard has its own authentication mechanism.
     *
     * @var bool
     */
    protected $requiresAuthentication = false;

    /**
     * Whether the controller requires configuration
     *
     * The install wizard does not require any configuration.
     *
     * @var bool
     */
    protected $requiresConfiguration = false;

    /**
     * Show the wizard and run the installation once its finished
     */
    public function indexAction()
    {
        $wizard = $this->createWizard();

        if ($wizard->isSubmittedAndValid()) {
            $wizard->navigate();
            if ($wizard->isFinished()) {
                // TODO: Run the installer (Who creates an installer? How do we handle module installers?)
                $this->dropConfiguration(); // TODO: Should only be done if the installation has been successfully completed
                $this->view->installer = '';
            } else {
                $this->storeConfiguration($wizard->getConfig());
            }
        }

        $this->view->wizard = $wizard;
    }

    /**
     * Create the wizard and register all pages
     *
     * @return  Wizard
     */
    protected function createWizard()
    {
        $wizard = new Wizard();
        $wizard->setTitle('Web');
        $wizard->setRequest($this->getRequest());
        $wizard->setConfiguration($this->loadConfiguration());
        $wizard->addPages(
            array(
//                t('Welcome') => 'Icinga\Form\Install\WelcomePage',
//                t('Requirements') => 'Icinga\Form\Install\RequirementsPage',
//                t('Authentication') => 'Icinga\Form\Install\AuthenticationPage',
//                t('Administration') => 'Icinga\Form\Install\AdministrationPage',
//                t('Preferences') => 'Icinga\Form\Install\PreferencesPage',
                t('Logging') => 'Icinga\Form\Install\LoggingPage',
//                t('Database Setup') => 'Icinga\Form\Install\DatabasePage',
//                t('Summary') => 'Icinga\Form\Install\SummaryPage'
            )
        );

        return $wizard;
    }

    /**
     * Store the given configuration values
     *
     * @param   Zend_Config     $config     The configuration
     */
    protected function storeConfiguration(Zend_Config $config)
    {
        $session = Session::getSession();
        $session->getNamespace('WebWizard')->setAll($config->toArray(), true);
        $session->write();
    }

    /**
     * Load all configuration values
     *
     * @return  Zend_Config
     */
    protected function loadConfiguration()
    {
        return new Zend_Config(Session::getSession()->getNamespace('WebWizard')->getAll(), true);
    }

    /**
     * Clear all stored configuration values
     */
    protected function dropConfiguration()
    {
        $session = Session::getSession();
        $session->removeNamespace('WebWizard');
        $session->write();
    }
}
