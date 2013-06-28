<?php
// @codingStandardsIgnoreStart
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

# namespace Icinga\Application\Controllers;

use Icinga\Web\ActionController;
use Icinga\Authentication\Credentials as Credentials;
use Icinga\Authentication\Manager as AuthManager;
use Icinga\Form\Builder as FormBuilder;

/**
 * Class AuthenticationController
 * @package Icinga\Application\Controllers
 */
class AuthenticationController extends ActionController
{
    /**
     * @var bool
     */
    protected $handlesAuthentication = true;

    /**
     * @var bool
     */
    protected $modifiesSession = true;

    private function getAuthForm()
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
                    'required' => true
                )
            ),
            'submit' => array(
                'submit',
                array(
                    'label' => t('Login'),
                    'class' => 'pull-right'
                )
            )
        );
    }

    /**
     *
     */
    public function loginAction()
    {
        $this->replaceLayout = true;
        $credentials = new Credentials();
        $this->view->form = FormBuilder::fromArray(
            $this->getAuthForm(),
            array(
                "CSRFProtection" => false, // makes no sense here
                "model" => &$credentials
            )
        );
        try {
            $auth = AuthManager::getInstance(null, array(
                "writeSession" => true 
            ));
            if ($auth->isAuthenticated()) {
                $this->redirectNow('index?_render=body');
            }
            if ($this->getRequest()->isPost() && $this->view->form->isSubmitted()) {
                $this->view->form->repopulate();
                if ($this->view->form->isValid()) {
                    if (!$auth->authenticate($credentials)) {
                        $this->view->form->getElement('password')->addError(t('Please provide a valid username and password'));
                    } else {
                        $this->redirectNow('index?_render=body');
                    }
                }
            }
        } catch (\Icinga\Exception\ConfigurationError $configError) {
            $this->view->errorInfo = $configError->getMessage();
        }
    }

    /**
     *
     */
    public function logoutAction()
    {
        $auth = AuthManager::getInstance(null, array(
            "writeSession" => true 
        ));
        $this->replaceLayout = true;
        $auth->removeAuthorization();
        $this->_forward('login');
    }
}

// @codingStandardsIgnoreEnd
