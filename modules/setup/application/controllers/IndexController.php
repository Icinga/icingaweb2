<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Web\Controller;
use Icinga\Module\Setup\WebWizard;

class Setup_IndexController extends Controller
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
     * Show the web wizard and run the configuration once finished
     */
    public function indexAction()
    {
        $wizard = new WebWizard();

        if ($wizard->isFinished()) {
            $setup = $wizard->getSetup();
            $success = $setup->run();
            if ($success) {
                $wizard->clearSession();
            } else {
                $wizard->setIsFinished(false);
            }

            $this->view->success = $success;
            $this->view->report = $setup->getReport();
        } else {
            $wizard->handleRequest();
        }

        $this->view->wizard = $wizard;
    }
}
