<?php

namespace Icinga\Forms\Authentication;

use Exception;
use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Authentication\IcingaTotp;
use Icinga\Web\Session;
use Icinga\Web\Url;

class Challenge2FAForm extends LoginForm
{
    public function init()
    {
        $this->setRequiredCue(null);
        $this->setName('form_challenge_2fa');
        $this->setSubmitLabel($this->translate('Verify'));
        $this->setProgressLabel($this->translate('Verifying'));
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'token',
            [
                'required' => true,
                'class' => 'autofocus content-centered',
                'placeholder' => $this->translate('Please enter your 2FA token'),
                'autocomplete' => 'off',
                'autocapitalize' => 'off',

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

    public function onSuccess()
    {
        $user = Auth::getInstance()->getUser();
        $totp = IcingaTotp::loadFromDb($this->getDb(), $user->getUsername());
        if ($this->getElement('token') && $totp->verify($this->getValue('token'))) {
            $auth = Auth::getInstance();
            $user = $auth->getUser();
            $user->setTwoFactorSuccessful(true);

            Session::getSession()->delete('2fa_must_challenge_token');

            $auth->setAuthenticated($user);

            if ($rememberMe = Session::getSession()->get('2fa_remember_me_cookie')) {
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

            return true;
        }

        $this->getElement('token')->addError($this->translate('Token is invalid!'));

        return false;
    }
}
