<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Authentication;

use Exception;
use Icinga\Application\Config;
use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Common\Database;
use Icinga\Exception\Http\HttpBadRequestException;
use Icinga\User;
use Icinga\Web\RememberMe;
use Icinga\Web\Response;
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

    /**
     * Redirect URL
     */
    const REDIRECT_URL = 'dashboard';

    public function __construct()
    {
        $this->setAttribute('name', 'form_login');
    }

    /**
     * Return the current Response
     *
     * @return Response
     */
    protected function getResponse(): Response
    {
        return Icinga::app()->getFrontController()->getResponse();
    }

    public function assemble(): void
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
                'class'          => ! isset($formData['username']) ? 'autofocus' : '',
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
                'class'        => isset($formData['username']) ? 'autofocus' : '',
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
            'btn_submit',
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

    /**
     * @return string|null
     * @throws HttpBadRequestException
     */
    public function createRedirectUrl(): string|null
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
            $auth->setAuthenticated($user);

            // If user has 2FA enabled and the token hasn't been validated, redirect to login again, so that
            // the token is challenged.
            if ($user->getTwoFactorEnabled() && ! $user->getTwoFactorSuccessful()) {
                $redirect = $this->getElement('redirect');
                $redirect->setValue(
                    Url::fromPath('authentication/login', ['redirect' => $redirect->getValue()])->getRelativeUrl()
                );
                Session::getSession()->set('2fa_must_challenge_token', true);

                if ($this->getElement('rememberme')->isChecked()) {
                    $rememberMe = RememberMe::fromCredentials($user->getUsername(), $password);
                    Session::getSession()->set('2fa_remember_me_cookie', $rememberMe);
                }

                $this->setRedirectUrl($this->createRedirectUrl());

                return;
            }

            if ($this->getElement('rememberme')->isChecked()) {
                try {
                    $rememberMe = RememberMe::fromCredentials($user->getUsername(), $password);
                    $this->getResponse()->setCookie($rememberMe->getCookie());
                    $rememberMe->persist();
                } catch (Exception $e) {
                    Logger::error('Failed to let user "%s" stay logged in: %s', $user->getUsername(), $e);
                }
            }

            // Call provided AuthenticationHook(s) after successful login
            AuthenticationHook::triggerLogin($user);

            $this->getResponse()->setRerenderLayout(true);
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

        $this->onError();
    }

    // Expose protected method onError() to use it in event listener callbacks
    public function onError(): void
    {
        parent::onError();
    }
}
