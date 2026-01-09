<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Authentication;

use Exception;
use Icinga\Application\Config;
use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Authentication\TwoFactorTotp;
use Icinga\Common\Database;
use Icinga\Exception\Http\HttpBadRequestException;
use Icinga\User;
use Icinga\Web\Form\Validator\TotpTokenValidator;
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

    /** @var string Redirect URL */
    const REDIRECT_URL = 'dashboard';

    /** @var string */
    const SUBMIT_LOGIN = 'btn_submit_login';

    /** @var string */
    const SUBMIT_VERIFY_2FA = 'btn_submit_verify_2fa';

    /** @var string */
    const SUBMIT_CANCEL_2FA = 'btn_submit_cancel_2fa';

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

    /** @return void */
    public function assembleLoginElements(): void
    {
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
            static::SUBMIT_LOGIN,
            [
                'label'               => $this->translate('Login'),
                'data-progress-label' => $this->translate('Logging in'),
            ]
        );
    }

    /** @return void */
    public function assembleTwoFactorElements(): void
    {
        $this->addElement(
            'text',
            'token',
            [
                'required'       => true,
                'class'          => 'autofocus content-centered',
                'placeholder'    => $this->translate('Please enter your 2FA token'),
                'autocomplete'   => 'off',
                'autocapitalize' => 'off',
                'decorators'     => [
                    'RenderElement' => new RenderElementDecorator(),
                    'Errors'        => ['name' => 'Errors', 'options' => ['class' => 'errors']]
                ],
                'validators'     => [new TotpTokenValidator()]
            ]
        );

        $this->addElement(
            'submit',
            static::SUBMIT_VERIFY_2FA,
            [
                'data-progress-label' => $this->translate('Verifying'),
                'label'               => $this->translate('Verify'),
            ]
        );

        $this->addElement(
            'submit',
            static::SUBMIT_CANCEL_2FA,
            [
                'ignore'              => true,
                'formnovalidate'      => true,
                'class'               => 'btn-cancel',
                'label'               => $this->translate('Cancel'),
                'data-progress-label' => $this->translate('Canceling')
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

    protected function assemble(): void
    {
        $this->addCsrfCounterMeasure(Session::getSession()->getId());
        $this->addElement($this->createUidElement());

        if (Session::getSession()->get('2fa_must_challenge_token', false)) {
            $this->assembleTwoFactorElements();
        } else {
            $this->assembleLoginElements();
        }

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
        switch ($this->getPressedSubmitElement()?->getName()) {
            case static::SUBMIT_LOGIN:
                $auth = Auth::getInstance();
                $authChain = $auth->getAuthChain();
                $authChain->setSkipExternalBackends(true);
                $username = $this->getElement('username')->getValue();
                $user = new User($username);
                // Set 2FA status on the user object depending on whether a secret exists for the user
                $user->setTwoFactorEnabled(TwoFactorTotp::hasDbSecret($this->getDb(), $username));
                if (! $user->hasDomain()) {
                    $user->setDomain(Config::app()->get('authentication', 'default_domain'));
                }
                $password = $this->getElement('password')->getValue();
                $authenticated = $authChain->authenticate($user, $password);
                if ($authenticated) {
                    if (! $user->getTwoFactorEnabled()) {
                        $auth->setAuthenticated($user);
                    } else {
                        $session = Session::getSession();
                        $session->set('2fa_must_challenge_token', true);
                        $session->set('2fa_temporary_user', $user);

                        if ($this->getElement('rememberme')->isChecked()) {
                            $rememberMe = RememberMe::fromCredentials($user->getUsername(), $password);
                            $session->set('2fa_remember_me_cookie', $rememberMe);
                        }

                        $this->setRedirectUrl(Url::fromRequest());

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

                break;

            case static::SUBMIT_VERIFY_2FA:
                $session = Session::getSession();
                /** @var User $user */
                $user = $session->get('2fa_temporary_user');
                $twoFactor = TwoFactorTotp::loadFromDb($this->getDb(), $user->getUsername());
                if ($this->getElement('token') && $twoFactor->verify($this->getValue('token'))) {
                    $user->setTwoFactorSuccessful();
                    $session->delete('2fa_must_challenge_token');
                    Auth::getInstance()->setAuthenticated($user);

                    if ($rememberMe = $session->get('2fa_remember_me_cookie')) {
                        try {
                            $this->getResponse()->setCookie($rememberMe->getCookie());
                            $rememberMe->persist();
                        } catch (Exception $e) {
                            Logger::error('Failed to let user "%s" stay logged in: %s', $user->getUsername(), $e);
                        }
                    }

                    // Call provided AuthenticationHook(s) after successful login
                    AuthenticationHook::triggerLogin($user);

                    $this->getResponse()->setRerenderLayout(true);

                    $this->setRedirectUrl(Url::fromRequest());

                    return;
                }

                $this->getElement('token')->addMessage($this->translate('Token is invalid!'));
        }

        // Display the messages that were added to form or form elements
        $this->onError();
    }

    // Expose protected method onError() to use it in event listener callbacks
    public function onError(): void
    {
        parent::onError();
    }
}
