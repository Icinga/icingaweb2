<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Authentication;

use Icinga\Application\Config;
use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Authentication\Auth;
use Icinga\Authentication\User\ExternalBackend;
use Icinga\User;
use Icinga\Web\Form;
use Icinga\Web\Url;

/**
 * Form for user authentication
 */
class LoginForm extends Form
{
    /**
     * Redirect URL
     */
    const REDIRECT_URL = 'dashboard';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setRequiredCue(null);
        $this->setName('form_login');
        $this->setSubmitLabel($this->translate('Login'));
        $this->setProgressLabel($this->translate('Logging in'));
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'username',
            array(
                'autocapitalize'    => 'off',
                'class'             => false === isset($formData['username']) ? 'autofocus' : '',
                'label'             => $this->translate('Username'),
                'required'          => true
            )
        );
        $this->addElement(
            'password',
            'password',
            array(
                'required'      => true,
                'label'         => $this->translate('Password'),
                'class'         => isset($formData['username']) ? 'autofocus' : ''
            )
        );
        $this->addElement(
            'hidden',
            'redirect',
            array(
                'value' => Url::fromRequest()->getParam('redirect')
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectUrl()
    {
        $redirect = null;
        if ($this->created) {
            $redirect = $this->getElement('redirect')->getValue();
        }
        if (empty($redirect) || strpos($redirect, 'authentication/logout') !== false) {
            $redirect = static::REDIRECT_URL;
        }
        return Url::fromPath($redirect);
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess()
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
            // Call provided AuthenticationHook(s) after successful login
            AuthenticationHook::triggerLogin($user);
            $this->getResponse()->setRerenderLayout(true);
            return true;
        }
        switch ($authChain->getError()) {
            case $authChain::EEMPTY:
                $this->addError($this->translate(
                    'No authentication methods available.'
                    . ' Did you create authentication.ini when setting up Icinga Web 2?'
                ));
                break;
            case $authChain::EFAIL:
                $this->addError($this->translate(
                    'All configured authentication methods failed.'
                    . ' Please check the system log or Icinga Web 2 log for more information.'
                ));
                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case $authChain::ENOTALL:
                $this->addError($this->translate(
                    'Please note that not all authentication methods were available.'
                    . ' Check the system log or Icinga Web 2 log for more information.'
                ));
                // Move to default
            default:
                $this->getElement('password')->addError($this->translate('Incorrect username or password'));
                break;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function onRequest()
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
            $this->addError($this->translate(
                'You\'re currently not authenticated using any of the web server\'s authentication mechanisms.'
                . ' Make sure you\'ll configure such, otherwise you\'ll not be able to login.'
            ));
        }
    }
}
