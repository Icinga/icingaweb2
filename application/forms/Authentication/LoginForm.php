<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Authentication;

use Exception;
use Icinga\Application\Config;
use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Application\Hook\TwoFactorHook;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Authentication\TwoFactorState;
use Icinga\Authentication\User\ExternalBackend;
use Icinga\User;
use Icinga\Web\Form\Element\LoginRedirect;
use Icinga\Web\RememberMe;
use Icinga\Web\Session;
use Icinga\Web\Url;
use ipl\Html\FormDecoration\ErrorsDecorator;
use ipl\Html\FormDecoration\HtmlTagDecorator;
use ipl\Html\FormDecoration\LabelDecorator;
use ipl\Html\FormDecoration\RenderElementDecorator;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Compat\FormDecorator\CheckboxDecorator;
use ipl\Web\Compat\FormDecorator\DescriptionDecorator;
use Throwable;

/**
 * Form for user authentication
 */
class LoginForm extends CompatForm
{
    use CsrfCounterMeasure;
    use FormUid;

    /** @var string Redirect URL */
    public const REDIRECT_URL = 'dashboard';

    /**
     * Create a new LoginForm
     */
    public function __construct()
    {
        $this->setAttribute('name', 'form_login');
        // Use a unique id so loader.js doesn't restore focus to the submit button
        // of a subsequently rendered form that would match the same selector.
        $this->setAttribute('id', 'login-form');
    }

    protected function assemble(): void
    {
        $this->addCsrfCounterMeasure(Session::getSession()->getId());
        $this->addElement($this->createUidElement());

        $this->addElement('text', 'username', [
            'autocapitalize' => 'off',
            'autocomplete'   => 'username',
            'autofocus'      => '',
            'decorators'     => [
                'RenderElement' => new RenderElementDecorator(),
                'Errors'        => (new ErrorsDecorator())->setClass('errors'),
                'ControlGroup'  => (new HtmlTagDecorator())->setTag('div')->setClass('control-group')
            ],
            'placeholder'    => $this->translate('Username'),
            'required'       => true
        ]);

        $this->addElement('password', 'password', [
            'autocomplete' => 'current-password',
            'decorators'   => [
                'RenderElement' => new RenderElementDecorator(),
                'Errors'        => (new ErrorsDecorator())->setClass('errors'),
                'ControlGroup'  => (new HtmlTagDecorator())->setTag('div')->setClass('control-group')
            ],
            'placeholder'  => $this->translate('Password'),
            'required'     => true
        ]);

        $rememberMeSupported = RememberMe::isSupported();
        $this->addElement('checkbox', 'rememberme', [
            'decorators'  => [
                'Checkbox'      => new CheckboxDecorator(),
                'RenderElement' => new RenderElementDecorator(),
                'Label'         => new LabelDecorator(),
                'Description'   => new DescriptionDecorator(),
                'ControlGroup'  => (new HtmlTagDecorator())->setTag('div')->setClass('control-group remember-me-box')
            ],
            'description' => ! $rememberMeSupported
                ? $this->translate(
                    'Staying logged in requires a database configuration backend'
                    . ' and an appropriate OpenSSL encryption method'
                )
                : null,
            'disabled'    => ! $rememberMeSupported,
            'label'       => $this->translate('Stay logged in'),
        ]);

        $this->addElement('submit', 'submit_login', [
            'data-progress-label' => $this->translate('Logging in'),
            'label'               => $this->translate('Login')
        ]);

        $this->addElement(new LoginRedirect('redirect', ['value' => Url::fromRequest()->getParam('redirect')]));
    }

    /**
     * Authenticate the user and redirect on success, or display an error message on failure
     *
     * Skips external backends and applies the configured default domain when the
     * username contains no domain. If the user is enrolled in a two-factor method,
     * stores the challenge in the session and redirects to the two-factor challenge
     * page instead of completing login immediately; optionally persists the
     * RememberMe record at this point so it can be issued after the challenge
     * succeeds. On full success, persists the RememberMe cookie when requested,
     * triggers registered {@see AuthenticationHook}s, and redirects to the URL
     * returned by {@see LoginRedirect::getUrl()}. On failure, adds an appropriate
     * error message to the form and calls {@see onError()}.
     *
     * @return void
     */
    protected function onSuccess(): void
    {
        $auth = Auth::getInstance();
        $authChain = $auth->getAuthChain();
        $authChain->setSkipExternalBackends(true);
        $user = new User($this->getElement('username')->getValue());
        if (! $user->hasDomain()) {
            $user->setDomain(Config::app()->get('authentication', 'default_domain'));
        }
        $password = $this->getElement('password')->getValue();
        $authenticated = $authChain->authenticate($user, $password);
        if ($authenticated) {
            try {
                $twoFactor = TwoFactorHook::loadEnrolled($user);
            } catch (Throwable $e) {
                $this->logAndShowError($e, $this->translate(
                    'Two-factor authentication is currently unavailable: {error}. Contact your administrator.',
                ));

                return;
            }

            if ($twoFactor !== null) {
                $twoFactorState = new TwoFactorState(Session::getSession());
                $twoFactorState->challenge($user);
                Logger::info(
                    'User "%s" has been challenged for two-factor verification using method "%s"',
                    $user->getUsername(),
                    $twoFactor->getCanonicalName(),
                );

                if ($this->getElement('rememberme')->isChecked()) {
                    try {
                        $rememberMe = RememberMe::fromCredentials($user->getUsername(), $password);
                        $rememberMe->persist();
                        $twoFactorState->setRememberMeCookieData($rememberMe->getCookie()->getValue());
                    } catch (Throwable $e) {
                        Logger::error('Failed to let user "%s" stay logged in: %s', $user->getUsername(), $e);
                    }
                }

                Session::getSession()->refreshId();

                $redirectUrl = Url::fromPath('authentication/twofactor');
                if ($redirect = Url::fromRequest()->getParam('redirect')) {
                    $redirectUrl->setParam('redirect', $redirect);
                }

                $this->setRedirectUrl($redirectUrl);

                return;
            }

            $auth->setAuthenticated($user);
            $response = Icinga::app()->getResponse();
            if ($this->getElement('rememberme')->isChecked()) {
                try {
                    $rememberMe = RememberMe::fromCredentials($user->getUsername(), $password);
                    $rememberMe->persist();
                    $response->setCookie($rememberMe->getCookie());
                } catch (Exception $e) {
                    Logger::error('Failed to let user "%s" stay logged in: %s', $user->getUsername(), $e);
                }
            }

            // Call provided AuthenticationHook(s) after successful login
            AuthenticationHook::triggerLogin($user);

            $response->setRerenderLayout();
            /** @var LoginRedirect $redirectElement */
            $redirectElement = $this->getElement('redirect');
            $this->setRedirectUrl($redirectElement->getUrl());

            return;
        }
        switch ($authChain->getError()) {
            case $authChain::EEMPTY:
                $this->addMessage($this->translate(
                    'No authentication methods available.'
                    . ' Did you create authentication.ini when setting up Icinga Web 2?'
                ));

                break;
            case $authChain::EFAIL:
                $this->addMessage($this->translate(
                    'All configured authentication methods failed.'
                    . ' Please check the system log or Icinga Web 2 log for more information.'
                ));

                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case $authChain::ENOTALL:
                $this->addMessage($this->translate(
                    'Please note that not all authentication methods were available.'
                    . ' Check the system log or Icinga Web 2 log for more information.'
                ));
            // Move to default
            default:
                $this->getElement('password')->addMessage($this->translate('Incorrect username or password'));
        }

        $this->onError();
    }

    /**
     * Show an error when all configured backends are external
     *
     * @return void
     */
    public function onRequest(): void
    {
        $auth = Auth::getInstance();
        $onlyExternal = true;
        // TODO(el): This may be set on the auth chain once iterated. See Auth::authExternal().
        foreach ($auth->getAuthChain() as $backend) {
            if (! $backend instanceof ExternalBackend) {
                $onlyExternal = false;
            }
        }
        if ($onlyExternal) {
            $this->addMessage($this->translate(
                'You\'re currently not authenticated using any of the web server\'s authentication'
                . ' mechanisms. Make sure you\'ll configure such, otherwise you\'ll not be able to login.'
            ));
            $this->onError();
        }
    }
}
