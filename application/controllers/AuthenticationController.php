<?php
// @codingStandardsIgnoreStart
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
use Icinga\Authentication\Credential;
use Icinga\Authentication\Manager as AuthManager;
use Icinga\Form\Authentication\LoginForm;
use Exception;

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
        $this->_helper->layout->setLayout('login');
        $this->view->form = new LoginForm();
        $this->view->form->setRequest($this->_request);
        $this->view->title = 'Icinga Web Login';
        try {
            $redirectUrl = $this->_request->getParam('redirect', 'index?_render=body');
            $auth = AuthManager::getInstance();
            if ($auth->isAuthenticated()) {
                $this->redirectNow($redirectUrl);
            }
            if ($this->view->form->isSubmittedAndValid()) {
                $credentials = new Credential(
                    $this->view->form->getValue('username'),
                    $this->view->form->getValue('password')
                );
                if (!$auth->authenticate($credentials)) {
                    $this->view->form->getElement('password')
                        ->addError(t('Please provide a valid username and password'));
                } else {
                    $this->redirectNow($redirectUrl);
                }
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
        $this->_helper->layout->setLayout('inline');
        $auth = AuthManager::getInstance();
        $auth->removeAuthorization();
        $this->redirectToLogin();
    }
}
// @codingStandardsIgnoreEnd
