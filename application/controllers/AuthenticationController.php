<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Application\Icinga;
use Icinga\Forms\Authentication\LoginForm;
use Icinga\Rememberme\Common\Database;
use Icinga\Web\Controller;
use Icinga\Web\Helper\CookieHelper;
use Icinga\Web\RememberMe;
use Icinga\Web\Url;

/**
 * Application wide controller for authentication
 */
class AuthenticationController extends Controller
{
    use Database;
    /**
     * {@inheritdoc}
     */
    protected $requiresAuthentication = false;

    /**
     * {@inheritdoc}
     */
    protected $innerLayout = 'inline';

    /**
     * Log into the application
     */
    public function loginAction()
    {
        //TODO var_dump(substr($_SERVER["REQUEST_URI"],12));die;
        $authenticatedByCookie = false;
        $icinga = Icinga::app();
        if (($requiresSetup = $icinga->requiresSetup()) && $icinga->setupTokenExists()) {
            $this->redirectNow(Url::fromPath('setup'));
        }
        $form = new LoginForm();
        if (RememberMe::hasCookie()) {
            if (RememberMe::fromCookie()->authenticate()) {
                // Call provided AuthenticationHook(s) when login action is called
                // but icinga web user is already authenticated
                AuthenticationHook::triggerLogin($this->Auth()->getUser());
                $this->redirectNow($this->params->get('redirect'));
            } else {
                $this->getResponse()->setCookie(
                    RememberMe::unsetCookie()
                );
                (new RememberMe())->remove($this->Auth()->getUser()->getUsername());
            }
        } else {
            if ($this->Auth()->isAuthenticated()) {
                AuthenticationHook::triggerLogin($this->Auth()->getUser());
                $this->redirectNow($form->getRedirectUrl());
            }
        }
        if (! $requiresSetup) {
            $cookies = new CookieHelper($this->getRequest());
            if (! $cookies->isSupported()) {
                $this
                    ->getResponse()
                    ->setBody("Cookies must be enabled to run this application.\n")
                    ->setHttpResponseCode(403)
                    ->sendResponse();
                exit;
            }
            $form->handleRequest();
        }

        $this->view->form = $form;
        $this->view->defaultTitle = $this->translate('Icinga Web 2 Login');
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
        $user = $auth->getUser();
        // Get info whether the user is externally authenticated before removing authorization which destroys the
        // session and the user object
        $isExternalUser = $user->isExternalUser();
        // Call provided AuthenticationHook(s) when logout action is called
        AuthenticationHook::triggerLogout($user);
        $auth->removeAuthorization();
        if ($isExternalUser) {
            $this->view->layout()->setLayout('external-logout');
            $this->getResponse()->setHttpResponseCode(401);
        } else {
            if (RememberMe::hasCookie()) {
                $this->getResponse()->setCookie(
                    RememberMe::unsetCookie()
                );
            }
            (new RememberMe())->remove($user->getUsername());
            $this->redirectToLogin();
        }
    }
}
