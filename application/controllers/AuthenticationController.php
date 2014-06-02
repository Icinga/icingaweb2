<?php
// @codeCoverageIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

# namespace Icinga\Application\Controllers;

use Icinga\Web\Controller\ActionController;
use Icinga\Authentication\Manager as AuthManager;
use Icinga\Form\Authentication\LoginForm;
use Icinga\Authentication\AuthChain;
use Icinga\Application\Config;
use Icinga\Logger\Logger;
use Icinga\Exception\AuthenticationException;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\ConfigurationError;
use Icinga\User;
use Icinga\Web\Url;

/**
 * Application wide controller for authentication
 */
class AuthenticationController extends ActionController
{
    /**
     * This controller does not require authentication
     *
     * @var bool
     */
    protected $requiresAuthentication = false;

    /**
     * Log into the application
     */
    public function loginAction()
    {
        $this->view->form = new LoginForm();
        $this->view->form->setRequest($this->_request);
        $this->view->title = $this->translate('Icingaweb Login');

        try {
            $redirectUrl = Url::fromPath($this->_request->getParam('redirect', 'dashboard'));

            if ($this->_request->isXmlHttpRequest()) {
                $redirectUrl->setParam('_render', 'layout');
            }

            $auth = AuthManager::getInstance();
            if ($auth->isAuthenticated()) {
                $this->redirectNow($redirectUrl);
            }

            if ($this->view->form->isSubmittedAndValid()) {
                try {
                    $config = Config::app('authentication');
                } catch (NotReadableError $e) {
                    Logger::error(
                        new Exception('Cannot load authentication configuration. An exception was thrown:', 0, $e)
                    );
                    throw new ConfigurationError(
                        'No authentication methods available. It seems that none authentication method has been set'
                        . ' up. Please check the system log or Icinga Web 2 log for more information'
                    );
                }
                $user = new User($this->view->form->getValue('username'));
                $password = $this->view->form->getValue('password');
                $backendsTried = 0;
                $backendsWithError = 0;
                $chain = new AuthChain($config);
                foreach ($chain as $backend) {
                    ++$backendsTried;
                    try {
                        $authenticated = $backend->authenticate($user, $password);
                    } catch (AuthenticationException $e) {
                        Logger::error($e);
                        ++$backendsWithError;
                        continue;
                    }
                    if ($authenticated === true) {
                        $auth->setAuthenticated($user);
                        $this->redirectNow($redirectUrl);
                    }
                }
                if ($backendsWithError === $backendsTried) {
                    throw new ConfigurationError(
                        $this->translate(
                            'No authentication methods available. It seems that all set up authentication methods have'
                            . ' errors. Please check the system log or Icinga Web 2 log for more information'
                        )
                    );
                }
                if ($backendsWithError) {
                    $this->view->form->addNote(
                        $this->translate(
                            'Note that not all authentication backends are available for authentication because they'
                            . ' have errors. Please check the system log or Icinga Web 2 log for more information'
                        )
                    );
                }
                $this->view->form->getElement('password')->addError($this->translate('Incorrect username or password'));
            }
        } catch (Exception $e) {
            $this->view->errorInfo = $e->getMessage();
        }
    }

    /**
     * Log out the current user
     */
    public function logoutAction()
    {
        $auth = AuthManager::getInstance();
        $auth->removeAuthorization();

        if ($auth->isAuthenticatedFromRemoteUser()) {
            $this->_helper->layout->setLayout('login');
            $this->_response->setHttpResponseCode(401);
        } else {
            $this->_helper->layout->setLayout('inline');
            $this->redirectToLogin();
        }
    }
}
// @codeCoverageIgnoreEnd
