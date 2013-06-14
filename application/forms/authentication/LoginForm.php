<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form;

use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Application\Config;
use Icinga\Authentication\Backend as AuthBackend;
use Icinga\Authentication\Auth;

/**
 * Class LoginForm
 * @package Icinga\Web\Form
 */
class LoginForm extends Form
{
    /**
     *
     */
    public function onSuccess()
    {
        $backend = new AuthBackend(Config::getInstance()->authentication);
        $values = $this->getValues();
        $username = $values['username'];
        $password = $values['password'];
        if ($backend->hasUsername($username)) {
            if ($user = $backend->authenticate($username, $password)) {
                // \Zend_Session::regenerateId();
                Auth::getInstance()->setAuthenticatedUser($user);
                Notification::success('Login succeeded');
                $this->redirectNow('index?_render=body');
            } else {
                // TODO: Log "auth failed"
            }
        } else {
            // TODO: Log "User does not exist"
        }

        $this->getElement('password')->addError(
            t(
                'Authentication failed, please check username and password'
            )
        );
    }

    /**
     * @return array
     */
    public function elements()
    {
        return array(
            'username' => array(
                'text',
                array(
                    'label' => t('Username'),
                    'required' => true,
                )
            ),
            'password' => array(
                'password',
                array(
                    'label' => t('Password'),
                    'required' => true,
                )
            ),
            'submit' => array(
                'submit',
                array(
                    'label' => t('Login')
                )
            )
        );
    }
}
