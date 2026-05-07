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
use Icinga\Authentication\TwoFactorState;
use Icinga\Common\Database;
use Icinga\Exception\AuthenticationException;
use Icinga\Forms\Authentication\LoginForm;
use Icinga\Forms\Authentication\TwoFactorChallengeForm;
use Icinga\Web\Helper\CookieHelper;
use Icinga\Web\RememberMe;
use Icinga\Web\Session;
use Icinga\Web\Url;
use Icinga\Web\Widget\LoginPage;
use ipl\Html\Contract\Form;
use ipl\Web\Compat\CompatController;
use Psr\Http\Message\ServerRequestInterface;
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
        if ((new TwoFactorState(Session::getSession()))->isChallenged()) {
            $this->redirectNow($this->withRedirect('authentication/twofactor'));
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
            ->on(Form::ON_REQUEST, function (ServerRequestInterface $_, LoginForm $form) {
                $form->onRequest();
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
                    $this->httpBadRequest('Redirect to an external host is not allowed');
                }
            } else {
                $redirectUrl = Url::fromPath(LoginForm::REDIRECT_URL);
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
                        ->on(Form::ON_SUBMIT, function () use ($button): void {
                            ($button->onClick)();
                        })
                        ->handleRequest($request);
                }
            } catch (Throwable $e) {
                Logger::error('Failed to execute login button hook: %s', $e);
                continue;
            }
        }

        // Suppress the rendering of controls bar.
        $this->view->compact = true;
        $this->setTitle($this->translate('Icinga Web 2 Login'));
        $this->addContent(new LoginPage($form, $loginButtons, $requiresSetup));
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

    /**
     * Render the two-factor authentication challenge page
     *
     * @return void
     */
    public function twofactorAction(): void
    {
        $session = Session::getSession();
        if (! (new TwoFactorState($session))->isChallenged()) {
            $this->redirectToLogin();
        }

        $form = (new TwoFactorChallengeForm())
            ->setCsrfCounterMeasureId($session->getId())
            ->setAction(Url::fromRequest()->getAbsoluteUrl())
            ->on(Form::ON_SUBMIT, function (TwoFactorChallengeForm $form) {
                if ($redirectUrl = $form->getRedirectUrl()) {
                    $this->redirectNow($redirectUrl);
                }
            })
            ->on(Form::ON_SENT, function (TwoFactorChallengeForm $form) use ($session) {
                // ON_SENT because cancel is not the primary submit button and never
                // triggers ON_SUBMIT. CSRF is checked manually. Without it a forged
                // request could destroy the session and drop the 2FA challenge.
                $csrfValid = $form->getElement('CSRFToken')->isValid();
                $cancelPressed =
                    $form->getPressedSubmitElement()?->getName() === TwoFactorChallengeForm::SUBMIT_CANCEL;

                if ($csrfValid && $cancelPressed) {
                    $session->purge();
                    $this->redirectNow($this->withRedirect('authentication/login'));
                }
            })
            ->handleRequest($this->getServerRequest());

        $this->setTitle($this->translate('Icinga Web 2 Two-Factor Auth'));

        // Suppress the rendering of controls bar.
        $this->view->compact = true;
        $this->addContent(new LoginPage($form));
    }

    /**
     * Build a URL for the given path that carries forward the redirect destination
     *
     * Copies the `redirect` query parameter from the current request, if present, so the
     * post-authentication destination is not lost during multi-step authentication transitions.
     *
     * @param string $path Path to build the URL from
     *
     * @return Url
     */
    protected function withRedirect(string $path): Url
    {
        $url = Url::fromPath($path);
        if ($redirect = $this->params->get('redirect')) {
            $url->setParam('redirect', $redirect);
        }

        return $url;
    }
}
