<?php

/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Authentication;

use Exception;
use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Authentication\TwoFactorTotp;
use Icinga\Common\Database;
use Icinga\Web\Response;
use Icinga\Web\Session;
use Icinga\Web\Url;
use ipl\Html\Attributes;
use ipl\Html\FormDecoration\RenderElementDecorator;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;

class Challenge2FAForm extends CompatForm
{
    use CsrfCounterMeasure;
    use Database;
    use FormUid;

    const SUBMIT_VERIFY = 'btn_submit_verify';

    const SUBMIT_CANCEL = 'btn_submit_cancel';

    public function __construct()
    {
        $this->addAttributes(Attributes::create(['name' => '2fa_challenge_form']));
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

    protected function assemble(): void
    {
        $this->addCsrfCounterMeasure(Session::getSession()->getId());
        $this->addElement($this->createUidElement());

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
                ]
            ]
        );

        $this->addElement(
            'submit',
            self::SUBMIT_VERIFY,
            [
                'data-progress-label' => $this->translate('Verifying'),
                'label'               => $this->translate('Verify'),
            ]
        );

        $this->addElement(
            'submit',
            self::SUBMIT_CANCEL,
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

    protected function onSuccess(): void
    {
        $user = Auth::getInstance()->getUser();
        $twoFactor = TwoFactorTotp::loadFromDb($this->getDb(), $user->getUsername());
        if ($this->getElement('token') && $twoFactor->verify($this->getValue('token'))) {
            $auth = Auth::getInstance();
            $user = $auth->getUser();
            $user->setTwoFactorSuccessful();

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

            $this->setRedirectUrl(Url::fromRequest());
        }

        $this->getElement('token')->addMessage($this->translate('Token is invalid!'));
    }
}
