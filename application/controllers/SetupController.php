<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\WebSetup;
use Icinga\Web\Controller\ActionController;

class SetupController extends ActionController
{
    /**
     * Whether the controller requires the user to be authenticated
     *
     * FALSE as the wizard uses token authentication
     *
     * @var bool
     */
    protected $requiresAuthentication = false;

    /**
     * Show the web wizard and run the installation once finished
     */
    public function indexAction()
    {
        $wizard = new WebSetup();

        if ($wizard->isFinished()) {
            $installer = $wizard->getInstaller();
            $success = $installer->run();
            if ($success) {
                $wizard->clearSession();
            } else {
                $wizard->setIsFinished(false);
            }

            $this->view->success = $success;
            $this->view->report = $installer->getReport();
        } else {
            $wizard->handleRequest();
        }

        $this->view->wizard = $wizard;
    }
}
