<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Authentication;

use Exception;
use Icinga\Application\Config;
use Icinga\Application\Hook\AuthenticationHook;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Authentication\User\ExternalBackend;
use Icinga\Common\Database;
use Icinga\Exception\Http\HttpBadRequestException;
use Icinga\User;
use Icinga\Web\Form;
use Icinga\Web\RememberMe;
use Icinga\Web\Url;

/**
 * Form for user authentication
 */
class LoginForm extends Form
{
    use Database;

    const DEFAULT_CLASSES = 'icinga-controls';

    /**
     * Redirect URL
     */
    const REDIRECT_URL = 'dashboard';

    public static $defaultElementDecorators = [
        ['ViewHelper', ['separator' => '']],
        ['Help', []],
        ['Errors', ['separator' => '']],
        ['HtmlTag', ['tag' => 'div', 'class' => 'control-group']]
    ];

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
                'autocomplete'      => 'username',
                'class'             => false === isset($formData['username']) ? 'autofocus' : '',
                'placeholder'       => $this->translate('Username'),
                'required'          => true
            )
        );
        $this->addElement(
            'password',
            'password',
            array(
                'required'      => true,
                'autocomplete'  => 'current-password',
                'placeholder'   => $this->translate('Password'),
                'class'         => isset($formData['username']) ? 'autofocus' : ''
            )
        );
        $this->addElement(
            'checkbox',
            'rememberme',
            [
                'label'         => $this->translate('Stay logged in'),
                'decorators'    => [
                    ['ViewHelper', ['separator' => '']],
                    ['Label', [
                        'tag'       => 'span',
                        'separator' => '',
                        'class'     => 'control-label',
                        'placement' => 'APPEND'
                    ]],
                    ['Help', []],
                    ['Errors', ['separator' => '']],
                    ['HtmlTag', ['tag' => 'div', 'class' => 'control-group remember-me-box']]
                ]
            ]
        );
        if (! RememberMe::isSupported()) {
            $this->getElement('rememberme')
                ->setAttrib('disabled', true)
                ->setDescription($this->translate(
                    'Staying logged in requires a database configuration backend'
                    . ' and an appropriate OpenSSL encryption method'
                ));
        }

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

        $redirectUrl = Url::fromPath($redirect);
        if ($redirectUrl->isExternal()) {
            throw new HttpBadRequestException('nope');
        }

        return $redirectUrl;
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
