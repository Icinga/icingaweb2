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
use Icinga\Common\Database;
use Icinga\Exception\Http\HttpBadRequestException;
use Icinga\User;
use Icinga\Web\RememberMe;
use Icinga\Web\Session;
use Icinga\Web\Url;
use ipl\Html\FormDecoration\LabelDecorator;
use ipl\Html\FormDecoration\RenderElementDecorator;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Compat\FormDecorator\CheckboxDecorator;

/**
 * Form for user authentication
 */
class LoginForm extends CompatForm
{
    use CsrfCounterMeasure;
    use Database;
    use FormUid;

    /** @var string Redirect URL */
    const REDIRECT_URL = 'dashboard';

    public function __construct()
    {
        $this->setAttribute('name', 'form_login');
    }

    protected function assemble(): void
    {
        $this->addCsrfCounterMeasure(Session::getSession()->getId());
        $this->addElement($this->createUidElement());

        $this->addElement(
            'text',
            'username',
            [
                'required'       => true,
                'autocomplete'   => 'username',
                'autocapitalize' => 'off',
                'class'          => $this->getPopulatedValue('username') === null ? 'autofocus' : '',
                'placeholder'    => $this->translate('Username'),
                'decorators'     => [
                    'RenderElement' => new RenderElementDecorator(),
                    'ControlGroup'  => [
                        'name'    => 'HtmlTag',
                        'options' => ['tag' => 'div', 'class' => 'control-group']
                    ]
                ]
            ]
        );

        $this->addElement(
            'password',
            'password',
            [
                'required'     => true,
                'autocomplete' => 'current-password',
                'class'        => $this->getPopulatedValue('username') !== null ? 'autofocus' : '',
                'placeholder'  => $this->translate('Password'),
                'decorators'   => [
                    'RenderElement' => new RenderElementDecorator(),
                    'Errors'        => ['name' => 'Errors', 'options' => ['class' => 'errors']],
                    'ControlGroup'  => [
                        'name'    => 'HtmlTag',
                        'options' => ['tag' => 'div', 'class' => 'control-group']
                    ]
                ]
            ]
        );

        $this->addElement(
            'checkbox',
            'rememberme',
            [
                'label'      => $this->translate('Stay logged in'),
                'disabled'   => ! RememberMe::isSupported(),
                'decorators' => [
                    'Checkbox'      => new CheckboxDecorator(),
                    'RenderElement' => new RenderElementDecorator(),
                    'Label'         => new LabelDecorator(),
                    'ControlGroup'  => [
                        'name'    => 'HtmlTag',
                        'options' => ['tag' => 'div', 'class' => 'control-group remember-me-box']
                    ]
                ]
            ]
        );

        $this->addElement(
            'submit',
            'submit_login',
            [
                'label'               => $this->translate('Login'),
                'data-progress-label' => $this->translate('Logging in'),
            ]
        );

        $this->addElement(
            'hidden',
            'redirect',
            [
                'value' => Url::fromRequest()->getParam('redirect')
            ]
        );
    }

    protected function onSuccess(): void
    {
        $auth = Auth::getInstance();
        $authChain = $auth->getAuthChain();
        $authChain->setSkipExternalBackends(true);
        $username = $this->getElement('username')->getValue();
        $user = new User($username);
        $twoFactorMethod = TwoFactorHook::loadEnrolled($user);
        $user->setTwoFactorEnabled($twoFactorMethod !== null);
        if (! $user->hasDomain()) {
            $user->setDomain(Config::app()->get('authentication', 'default_domain'));
        }
        $password = $this->getElement('password')->getValue();
        $authenticated = $authChain->authenticate($user, $password);
        if ($authenticated) {
            if ($user->getTwoFactorEnabled()) {
                $twoFactorState = new TwoFactorState();
                $twoFactorState->challenge($user);
                Logger::info(
                    'User "%s" has been challenged for two-factor verification using method "%s"',
                    $user->getUsername(),
                    $twoFactorMethod->getName()
                );

                if ($this->getElement('rememberme')->isChecked()) {
                    $rememberMe = RememberMe::fromCredentials($user->getUsername(), $password);
                    $twoFactorState->setRememberMeCookie($rememberMe);
                }

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
                    $response->setCookie($rememberMe->getCookie());
                    $rememberMe->persist();
                } catch (Exception $e) {
                    Logger::error('Failed to let user "%s" stay logged in: %s', $user->getUsername(), $e);
                }
            }

            // Call provided AuthenticationHook(s) after successful login
            AuthenticationHook::triggerLogin($user);

            $response->setRerenderLayout();
            $this->setRedirectUrl($this->createRedirectUrl());

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

        // Display the messages that were added to form or form elements
        $this->onError();
    }

    // Expose protected method onError() to use it in event listener callbacks
    public function onError(): void
    {
        parent::onError();
    }

    /**
     * @return ?string
     *
     * @throws HttpBadRequestException
     */
    public function createRedirectUrl(): ?string
    {
        $redirect = null;
        if ($this->hasBeenAssembled) {
            $redirect = $this->getElement('redirect')->getValue();
        }

        if (empty($redirect) || str_contains($redirect, 'authentication/logout')) {
            $redirect = static::REDIRECT_URL;
        }

        $redirectUrl = Url::fromPath($redirect);
        if ($redirectUrl->isExternal()) {
            throw new HttpBadRequestException('nope');
        }

        return $redirectUrl;
    }
}
