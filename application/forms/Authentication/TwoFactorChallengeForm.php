<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Authentication;

use Exception;
use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Application\Hook\TwoFactorHook;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Authentication\TwoFactorState;
use Icinga\Exception\Http\HttpBadRequestException;
use Icinga\Web\Session;
use Icinga\Web\Url;
use ipl\Html\Attributes;
use ipl\Html\FormDecoration\ErrorsDecorator;
use ipl\Html\FormDecoration\HtmlTagDecorator;
use ipl\Html\FormDecoration\RenderElementDecorator;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Widget\Icon;
use Throwable;

/**
 * Form for the two-factor authentication challenge
 */
class TwoFactorChallengeForm extends CompatForm
{
    use CsrfCounterMeasure;
    use FormUid;

    /** @var string Name of the token text input in the verification form */
    public const TOKEN = 'twofactor_token';

    /** @var string Name of the verify submit button in the verification form */
    public const SUBMIT_VERIFY = 'submit_twofactor_verify';

    /** @var string Name of the cancel submit button in the verification form */
    public const SUBMIT_CANCEL = 'submit_twofactor_cancel';

    /**
     * Create a new TwoFactorChallengeForm
     */
    public function __construct()
    {
        $this->setAttribute('name', 'form_twofactor_challenge');
    }

    protected function assemble(): void
    {
        $this->addCsrfCounterMeasure();
        $this->addElement($this->createUidElement());

        $this->addElement('text', static::TOKEN, [
            'autocomplete' => 'off',
            'autofocus'    => '',
            'class'        => 'content-centered',
            'decorators'   => [
                'RenderElement' => new RenderElementDecorator(),
                'Errors'        => (new ErrorsDecorator())->setClass('errors'),
                'ControlGroup'  => (new HtmlTagDecorator())->setTag('div')->setClass('control-group'),
            ],
            'placeholder'  => $this->translate('Please enter your 2FA token'),
            'required'     => true,
        ]);

        $this->addElement('submit', static::SUBMIT_VERIFY, [
            'data-progress-label' => $this->translate('Verifying'),
            'label'               => $this->translate('Verify'),
        ]);

        $this->addElement('submitButton', static::SUBMIT_CANCEL, [
            'class'          => 'btn-back-to-login-link',
            'formnovalidate' => true,
            'label'          => [
                new Icon('arrow-left'),
                HtmlElement::create('span', Attributes::create(), Text::create($this->translate('Back to login'))),
            ],
        ]);

        $this->addElement('hidden', 'redirect', ['value' => Url::fromRequest()->getParam('redirect')]);
    }

    /**
     * Verify the submitted two-factor token and complete login on success
     *
     * Retrieves the challenged user from the session and the enrolled 2FA method.
     * If the enrolled method is no longer available, adds an error message and
     * calls {@see onError()}. On a valid token, authenticates the user, optionally
     * issues the remember-me cookie stored in the challenge state, clears the
     * pending challenge from the session, triggers registered
     * {@see AuthenticationHook}s, and sets the post-login redirect URL. On an
     * invalid token, adds a validation error to the token field.
     *
     * @return void
     */
    protected function onSuccess(): void
    {
        $twoFactorState = new TwoFactorState(Session::getSession());
        $user = $twoFactorState->getChallengedUser();
        if ($user === null) {
            $this->logAndShowError(
                $this->translate('Session changed mid-request, challenged user no longer available'),
                $this->translate(
                    'Two-factor authentication is currently unavailable: {error}. Contact your administrator.',
                ),
            );

            return;
        }

        try {
            $twoFactor = TwoFactorHook::loadEnrolled($user);
        } catch (Throwable $e) {
            $this->logAndShowError($e, $this->translate(
                'Two-factor authentication is currently unavailable: {error}. Contact your administrator.',
            ));

            return;
        }

        if ($twoFactor === null) {
            // This can happen when another user disables the module that provides the 2FA method,
            // while the current user is still on the 2FA challenge form.
            $this->addMessage($this->translate(
                'Your two-factor authentication method is no longer available.'
                . ' If this is unexpected, contact your administrator.'
                . ' Otherwise use \'Back to login\'. You will not be prompted for a second factor.',
            ));
            $this->onError();

            return;
        }

        try {
            $verified = $twoFactor->verify($user, $this->getValue(static::TOKEN));
        } catch (Throwable $e) {
            $this->logAndShowError($e, $this->translate(
                'Two-factor verification is currently unavailable: {error}. Contact your administrator.',
            ));

            return;
        }

        if ($verified) {
            Logger::info(
                'User "%s" passed two-factor verification using method "%s"',
                $user->getUsername(),
                $twoFactor->getName(),
            );
            Auth::getInstance()->setAuthenticated($user);

            $response = Icinga::app()->getResponse();

            if ($rememberMe = $twoFactorState->getRememberMeCookie()) {
                try {
                    $response->setCookie($rememberMe->getCookie());
                    $rememberMe->persist();
                } catch (Exception $e) {
                    Logger::error('Failed to let user "%s" stay logged in: %s', $user->getUsername(), $e);
                }
            }

            $twoFactorState->completeChallenge();

            // Call provided AuthenticationHook(s) after successful login.
            AuthenticationHook::triggerLogin($user);

            $response->setRerenderLayout();
            $this->setRedirectUrl($this->createRedirectUrl());

            return;
        }

        Logger::warning(
            'Two-factor verification failed for user "%s" using method "%s"',
            $user->getUsername(),
            $twoFactor->getName(),
        );
        $this->getElement(static::TOKEN)->addMessage($this->translate('Token is invalid!'));
    }

    /**
     * @return Url
     *
     * @throws HttpBadRequestException
     */
    public function createRedirectUrl(): Url
    {
        $redirect = null;
        if ($this->hasBeenAssembled) {
            $redirect = $this->getElement('redirect')->getValue();
        }

        if (empty($redirect) || str_contains($redirect, 'authentication/logout')) {
            $redirect = LoginForm::REDIRECT_URL;
        }

        $redirectUrl = Url::fromPath($redirect);
        if ($redirectUrl->isExternal()) {
            throw new HttpBadRequestException('nope');
        }

        return $redirectUrl;
    }
}
