<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Controllers;

use Icinga\Module\Setup\WebWizard;
use Icinga\Web\Controller;
use Icinga\Web\Form;
use Icinga\Web\Url;

class IndexController extends Controller
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
     * {@inheritdoc}
     */
    protected $innerLayout = 'inline';

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

            $restartForm = new Form();
            $restartForm->setUidDisabled();
            $restartForm->setName('setup_restart_form');
            $restartForm->setAction(Url::fromPath('setup/index/restart'));
            $restartForm->setAttrib('class', 'restart-form');
            $restartForm->addElement(
                'button',
                'btn_submit',
                array(
                    'type'          => 'submit',
                    'value'         => 'btn_submit',
                    'escape'        => false,
                    'label'         => $this->view->icon('reply-all'),
                    'title'         => $this->translate('Restart the setup'),
                    'decorators'    => array('ViewHelper')
                )
            );

            $this->view->restartForm = $restartForm;
        }

        $this->view->wizard = $wizard;
    }

    /**
     * Reset session and restart the wizard
     */
    public function restartAction()
    {
        $this->assertHttpMethod('POST');

        $form = new Form(array(
            'onSuccess' => function () {
                $wizard = new WebWizard();
                $wizard->clearSession(false);
            }
        ));
        $form->setUidDisabled();
        $form->setRedirectUrl('setup');
        $form->setSubmitLabel('btn_submit');
        $form->handleRequest();
    }
}
