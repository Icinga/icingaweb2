<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

# namespace Icinga\Application\Controllers;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Forms\Authentication\LoginForm;
use Icinga\Web\Controller;
use Icinga\Web\Url;

/**
 * Application wide controller for authentication
 */
class AuthenticationController extends Controller
{
    /**
     * This controller does not require authentication
     *
     * @var bool
     */
    protected $requiresAuthentication = false;

    /**
     * Log into the application
     */
    public function loginAction()
    {
        $icinga = Icinga::app();
        if (($requiresSetup = $icinga->requiresSetup()) && $icinga->setupTokenExists()) {
            $this->redirectNow(Url::fromPath('setup'));
        }
        $form = new LoginForm();
        if ($this->Auth()->isAuthenticated()) {
            $this->redirectNow($form->getRedirectUrl());
        }
        if (! $requiresSetup) {
            $form->handleRequest();
        }
        $this->view->form = $form;
        $this->view->title = $this->translate('Icinga Web 2 Login');
        $this->view->requiresSetup = $requiresSetup;
    }

    /**
     * Log out the current user
     */
    public function logoutAction()
    {
        $auth = $this->Auth();
        if (! $auth->isAuthenticated()) {
            $this->redirectToLogin();
        }
        $isRemoteUser = $auth->getUser()->isRemoteUser();
        $auth->removeAuthorization();
        if ($isRemoteUser === true) {
            $this->getResponse()->setHttpResponseCode(401);
        } else {
            $this->redirectToLogin();
        }
    }
}
