<?php

namespace Icinga\Forms\Authentication;

use Icinga\Application\Hook\AuthenticationHook;
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
        // TODO: Implement proper 2FA code validation
        $user = Auth::getInstance()->getUser();
        $totp = IcingaTotp::loadFromDb($this->getDb(), $user->getUsername());
        if ($totp->verify($_POST['token'])) {
            $auth = Auth::getInstance();
            $user = $auth->getUser();
            Session::getSession()->set('challenged_successful_2fa_token', true);
            Session::getSession()->delete('must_challenge_2fa_token');

            $auth->setAuthenticated($user);

            AuthenticationHook::triggerLogin($user);
            $this->getResponse()->setRerenderLayout(true);
            return true;
        }

        $this->getElement('token')->addError($this->translate('Token is invalid!'));

        return false;
    }
}
