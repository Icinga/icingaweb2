<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Controllers;

use Icinga\Application\ClassLoader;
use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Application\Hook\LoginButtonHook;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\LoginButton;
use Icinga\Authentication\LoginButtonForm;
use Icinga\Authentication\Auth;
use Icinga\Authentication\TwoFactorState;
use Icinga\Authentication\User\ExternalBackend;
use Icinga\Common\Database;
use Icinga\Exception\AuthenticationException;
use Icinga\Forms\Authentication\LoginForm;
use Icinga\Forms\Authentication\TwoFactorChallengeForm;
use Icinga\Web\Helper\CookieHelper;
use Icinga\Web\RememberMe;
use Icinga\Web\Session;
use Icinga\Web\Url;
use Icinga\Web\Widget\LoginPage;
use ipl\Html\HtmlDocument;
use ipl\Html\Contract\Form;
use ipl\Web\Compat\CompatController;
use RuntimeException;
use Throwable;

/**
 * Application wide controller for authentication
 */
class AuthenticationController extends CompatController
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
        $twoFactorState = new TwoFactorState();
        if ($twoFactorState->isChallenged()) {
            $redirectUrl = Url::fromPath('authentication/twofactor');
            if ($redirect = Url::fromRequest()->getParam('redirect')) {
                $redirectUrl->setParam('redirect', $redirect);
            }
            $this->redirectNow($redirectUrl);
        }

        $icinga = Icinga::app();
        if (($requiresSetup = $icinga->requiresSetup()) && $icinga->setupTokenExists()) {
            $this->redirectNow(Url::fromPath('setup'));
        }

        $form = (new LoginForm())
            ->setAction(Url::fromRequest()->getAbsoluteUrl())
            ->on(Form::ON_SUBMIT, function (LoginForm $form) {
                if ($redirectUrl = $form->getRedirectUrl()) {
                    $this->redirectNow($redirectUrl);
                }
            })
            ->on(Form::ON_REQUEST, function ($request, LoginForm $form) {
                $auth = Auth::getInstance();
                $onlyExternal = true;
                // TODO(el): This may be set on the auth chain once iterated. See Auth::authExternal().
                foreach ($auth->getAuthChain() as $backend) {
                    if (! $backend instanceof ExternalBackend) {
                        $onlyExternal = false;
                    }
                }
                if ($onlyExternal) {
                    $form->addMessage($this->translate(
                        'You\'re currently not authenticated using any of the web server\'s authentication'
                        . 'mechanisms. Make sure you\'ll configure such, otherwise you\'ll not be able to login.'
                    ));
                    $form->onError();
                }
            });

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
                $redirectUrl = $form->createRedirectUrl();
            }

            $this->redirectNow($redirectUrl);
        }

        $request = $this->getServerRequest();
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
            $form->handleRequest($request);
        }

        $loginButtons = [];

        foreach (LoginButtonHook::all() as $class => $hook) {
            try {
                foreach ($hook->getButtons() as $index => $button) {
                    assert($button instanceof LoginButton);

                    $loginButtons[] = (new LoginButtonForm(
                        sha1("$class!$index"),
                        $button,
                        ClassLoader::classBelongsToModule($class) ? ClassLoader::extractModuleName($class) : null
                    ))
                        ->on(LoginButtonForm::ON_SUCCESS, function () use ($button): void {
                            ($button->onClick)();
                        })
                        ->handleRequest($request);
                }
            } catch (Throwable $e) {
                Logger::error('Failed to execute login button hook: %s', $e);
                continue;
            }
        }
        $this->setTitle($this->translate('Icinga Web 2 Login'));

        // Suppress the rendering of an empty tab bar
        $this->controls = new HtmlDocument();
        $content = array_filter(array_merge([$form], $loginButtons));
        $this->addContent(new LoginPage($content, $requiresSetup));
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

    public function twofactorAction(): void
    {
        $twoFactorState = new TwoFactorState();
        if (! $twoFactorState->isChallenged()) {
            $this->redirectToLogin();
        }

        $form = (new TwoFactorChallengeForm())
            ->setAction(Url::fromRequest()->getAbsoluteUrl())
            ->on(Form::ON_SUBMIT, function (TwoFactorChallengeForm $form) {
                if ($redirectUrl = $form->getRedirectUrl()) {
                    $this->redirectNow($redirectUrl);
                }
            })
            ->on(Form::ON_SENT, function (TwoFactorChallengeForm $form) {
                $isCsrfValid = $form->getElement('CSRFToken')->isValid();
                $isCancelPressed =
                    $form->getPressedSubmitElement()?->getName() === TwoFactorChallengeForm::SUBMIT_CANCEL_2FA;

                if ($isCsrfValid && $isCancelPressed) {
                    Session::getSession()->purge();
                    $redirectUrl = Url::fromPath('authentication/login');
                    if ($redirect = Url::fromRequest()->getParam('redirect')) {
                        $redirectUrl->setParam('redirect', $redirect);
                    }
                    $this->redirectNow($redirectUrl);
                }
            })
            ->handleRequest($this->getServerRequest());

        if ($this->Auth()->isAuthenticated()) {
            $redirect = $this->params->get('redirect');
            if ($redirect) {
                $redirectUrl = Url::fromPath($redirect, [], $this->getRequest());
                if ($redirectUrl->isExternal()) {
                    $this->httpBadRequest('nope');
                }
            } else {
                $redirectUrl = $form->createRedirectUrl();
            }

            $this->redirectNow($redirectUrl);
        }

        $this->setTitle($this->translate('Icinga Web 2 Two-Factor Auth'));

        // Suppress the rendering of an empty tab bar
        $this->controls = new HtmlDocument();
        $this->addContent(new LoginPage($form));
    }
}
