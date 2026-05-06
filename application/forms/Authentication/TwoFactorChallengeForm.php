<?php

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
use ipl\Html\FormDecoration\RenderElementDecorator;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Widget\Icon;

class TwoFactorChallengeForm extends CompatForm
{
    use CsrfCounterMeasure;
    use FormUid;

    /** @var string Name of the token text input in the verification form */
    const TOKEN = 'twofactor_token';

    /** @var string Name of the verify submit button in the verification form */
    const SUBMIT_VERIFY = 'submit_twofactor_verify';

    /** @var string Name of the cancel submit button in the verification form */
    const SUBMIT_CANCEL = 'submit_twofactor_cancel';

    public function __construct()
    {
        $this->setAttribute('name', 'form_twofactor_challenge');
    }

    protected function assemble(): void
    {
        $this->addCsrfCounterMeasure(Session::getSession()->getId());
        $this->addElement($this->createUidElement());

        $this->addElement('text', static::TOKEN, [
            'required'       => true,
            'class'          => 'autofocus content-centered',
            'placeholder'    => $this->translate('Please enter your 2FA token'),
            'autocomplete'   => 'off',
            'decorators'     => [
                'RenderElement' => new RenderElementDecorator(),
                'Errors'        => ['name' => 'Errors', 'options' => ['class' => 'errors']]
            ]
        ]);

        $this->addElement('submit', static::SUBMIT_VERIFY, [
            'data-progress-label' => $this->translate('Verifying'),
            'label'               => $this->translate('Verify')
        ]);

        $this->addElement('submitButton', static::SUBMIT_CANCEL, [
            'ignore'         => true,
            'formnovalidate' => true,
            'class'          => 'btn-back-to-login-link',
            'label'          => [
                new Icon('arrow-left'),
                HtmlElement::create('p', Attributes::create(), Text::create($this->translate('Back to login')))
            ]
        ]);

        $this->addElement('hidden', 'redirect', ['value' => Url::fromRequest()->getParam('redirect')]);
    }

    protected function onSuccess(): void
    {
        $twoFactorState = new TwoFactorState();
        $user = $twoFactorState->getChallengedUser();
        $twoFactorMethod = TwoFactorHook::loadEnrolled($user);
// TODO message is not shown | Is the check really needed? talk with eric
//        if ($twoFactorMethod === null) {
//            $this->addMessage($this->translate('No two-factor method is enabled'));
//
//            return;
//        }
        if ($twoFactorMethod->verify($this->getValue(static::TOKEN))) {
            Logger::info(
                'User "%s" passed two-factor verification using method "%s"',
                $user->getUsername(),
                $twoFactorMethod->getName()
            );
            $user->setTwoFactorSuccessful();
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

            // Call provided AuthenticationHook(s) after successful login
            AuthenticationHook::triggerLogin($user);

            $response->setRerenderLayout();
            $this->setRedirectUrl($this->createRedirectUrl());

            return;
        }

        Logger::warning(
            'Two-factor verification failed for user "%s" using method "%s"',
            $user->getUsername(),
            $twoFactorMethod->getName()
        );
        $this->getElement(static::TOKEN)->addMessage($this->translate('Token is invalid!'));
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
            $redirect = LoginForm::REDIRECT_URL;
        }

        $redirectUrl = Url::fromPath($redirect);
        if ($redirectUrl->isExternal()) {
            throw new HttpBadRequestException('nope');
        }

        return $redirectUrl;
    }
}
