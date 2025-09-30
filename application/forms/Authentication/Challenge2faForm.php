<?php

namespace Icinga\Forms\Authentication;

use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Authentication\Auth;
use Icinga\Authentication\IcingaTotp;
use Icinga\Authentication\Totp;
use Icinga\Web\Session;
use Icinga\Web\Url;

class Challenge2faForm extends LoginForm
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
            Session::getSession()->set('2fa_successfully_challenged_token', true);
            Session::getSession()->delete('2fa_must_challenge_token');

            $auth->setAuthenticated($user);

            // Call provided AuthenticationHook(s) after successful login
            AuthenticationHook::triggerLogin($user);
            
            $this->getResponse()->setRerenderLayout();

            return true;
        }

        $this->getElement('token')->addError($this->translate('Token is invalid!'));

        return false;
    }
}
