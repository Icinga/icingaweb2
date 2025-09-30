<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Common\Database;
use Icinga\Exception\AuthenticationException;
use Icinga\Forms\Authentication\Cancel2faForm;
use Icinga\Forms\Authentication\Challenge2faForm;
use Icinga\Forms\Authentication\LoginForm;
use Icinga\Web\Controller;
use Icinga\Web\Helper\CookieHelper;
use Icinga\Web\RememberMe;
use Icinga\Web\Session;
use Icinga\Web\Url;
use RuntimeException;

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
        $icinga = Icinga::app();
        if (($requiresSetup = $icinga->requiresSetup()) && $icinga->setupTokenExists()) {
            $this->redirectNow(Url::fromPath('setup'));
        }

        $user = $this->Auth()->getUser();
        if (
            $user
            && $user->getTwoFactorEnabled()
            && Session::getSession()->get('2fa_must_challenge_token', false)
        ) {
            $form = new Challenge2faForm();
            $cancel2faForm = new Cancel2faForm();
            $cancel2faForm->handleRequest();
        } else {
            $form = new LoginForm();

            if (RememberMe::hasCookie() && $this->hasDb()) {
                $authenticated = false;
                try {
                    $rememberMeOld = RememberMe::fromCookie();
                    $authenticated = $rememberMeOld->authenticate();
                    if ($authenticated) {
                        $rememberMe = $rememberMeOld->renew();
                        $this->getResponse()->setCookie($rememberMe->getCookie());
                        $rememberMe->persist($rememberMeOld->getAesCrypt()->getIV());
                    }
                } catch (RuntimeException $e) {
                    Logger::error("Can't authenticate user via remember me cookie: %s", $e->getMessage());
                } catch (AuthenticationException $e) {
                    Logger::error($e);
                }

                if (! $authenticated) {
                    $this->getResponse()->setCookie(RememberMe::forget());
                }
            }
        }

        if ($this->Auth()->isAuthenticated()) {
            // Call provided AuthenticationHook(s) when login action is called
            // but icinga web user is already authenticated
            AuthenticationHook::triggerLogin($this->Auth()->getUser());

            $redirect = $this->params->get('redirect');
            if ($redirect) {
                $redirectUrl = Url::fromPath($redirect, [], $this->getRequest());
                if ($redirectUrl->isExternal()) {
                    $this->httpBadRequest('nope');
                }
            } else {
                $redirectUrl = $form->getRedirectUrl();
            }

            $this->redirectNow($redirectUrl);
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
        $this->view->cancel2faForm = $cancel2faForm ?? null;
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
        // Get info whether the user is externally authenticated before removing authorization which destroys the
        // session and the user object
        $isExternalUser = $auth->getUser()->isExternalUser();
        // Call provided AuthenticationHook(s) when logout action is called
        AuthenticationHook::triggerLogout($auth->getUser());
        $auth->removeAuthorization();
        if ($isExternalUser) {
            $this->view->layout()->setLayout('external-logout');
            $this->getResponse()->setHttpResponseCode(401);
        } else {
            if (RememberMe::hasCookie() && $this->hasDb()) {
                $this->getResponse()->setCookie(RememberMe::forget());
            }

            $this->redirectToLogin();
        }
    }
}
