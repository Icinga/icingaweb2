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

use \Icinga\Web\Controller\ActionController;
use \Icinga\Authentication\Credential;
use \Icinga\Authentication\Manager as AuthManager;
use \Icinga\Form\Authentication\LoginForm;
use \Icinga\Exception\ConfigurationError;

/**
 * Application wide controller for authentication
 */
class AuthenticationController extends ActionController
{
    /**
     * This controller handles authentication
     *
     * @var bool
     */
    protected $requiresAuthentication = false;

    /**
     * This controller modifies the session
     *
     * @var bool
     *
     * @see \Icinga\Web\Controller\ActionController::$modifiesSession
     */
    protected $modifiesSession = true;

    /**
     * Log into the application
     */
    public function loginAction()
    {
        $this->replaceLayout = true;
        $credentials = new Credential();
        $this->view->form = new LoginForm();
        $this->view->form->setRequest($this->_request);
        $this->view->title = "Icinga Web Login";
        try {
            $auth = AuthManager::getInstance(null, array(
                'writeSession' => $this->modifiesSession
            ));

            if ($auth->isAuthenticated()) {
                $this->redirectNow('index?_render=body');
            }

            if ($this->view->form->isSubmittedAndValid()) {
                $credentials->setUsername($this->view->form->getValue('username'));
                $credentials->setPassword($this->view->form->getValue('password'));

                if (!$auth->authenticate($credentials)) {
                    $this->view->form->getElement('password')
                        ->addError(t('Please provide a valid username and password'));
                } else {
                    $redirectUrl = $this->_request->getParam('redirect');
                    if ($redirectUrl == null) {
                        $this->redirectNow('index?_render=body');
                    } else {
                        $this->redirectNow($redirectUrl);
                    }
                }
            }
        } catch (ConfigurationError $configError) {
            $this->view->errorInfo = $configError->getMessage();
        }
    }

    /**
     * Log out the current user
     */
    public function logoutAction()
    {
        $auth = AuthManager::getInstance(null, array(
            'writeSession' => $this->modifiesSession
        ));
        $this->replaceLayout = true;
        $auth->removeAuthorization();
        $this->redirectToLogin();
    }
}
// @codingStandardsIgnoreEnd
