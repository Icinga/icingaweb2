<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
 * Copyright (C) 2013 Icinga Development Team
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @author Icinga Development Team <info@icinga.org>
 */
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
