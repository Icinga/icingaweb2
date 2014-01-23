<?php
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

namespace Icinga\Web\Controller;

use \Zend_Log;
use Icinga\Web\Session;
use Icinga\User\Message;
use Icinga\Authentication\Manager as AuthenticationManager;

/**
 *  Base class for Configuration Controllers
 *
 *  Module preferences use this class to make sure they are automatically
 *  added to the application's configuration dialog. If you create a subclass of
 *  BasePreferenceController and overwrite @see init(), make sure you call
 *  parent::init(), otherwise you won't have the $tabs property in your view.
 */
class BaseConfigController extends ActionController
{
    /**
     * Send a message with the logging level Zend_Log::INFO to the current user and
     * commit the changes to the underlying session.
     *
     * @param $msg      The message content
     */
    protected function addSuccessMessage($msg)
    {
        AuthenticationManager::getInstance()->getUser()->addMessage(
            new Message($msg, Zend_Log::INFO)
        );
        Session::getSession()->write();
    }

    /**
     * Send a message with the logging level Zend_Log::ERR to the current user and
     * commit the changes to the underlying session.
     *
     * @param $msg      The message content
     */
    protected function addErrorMessage($msg)
    {
        AuthenticationManager::getInstance()->getUser()->addMessage(
            new Message($msg, Zend_Log::ERR)
        );
        Session::getSession()->write();
    }

    /*
     * Return an array of tabs provided by this configuration controller.
     *
     * Those tabs will automatically be added to the application's configuration dialog
     *
     * @return array
     */
    public static function createProvidedTabs()
    {
        return array();
    }

    /**
     * Initialize the controller and collect all tabs for it from the application and it's modules
     *
     * @see ActionController::init
     */
    public function init()
    {
        parent::init();
        $this->view->tabs = ControllerTabCollector::collectControllerTabs('ConfigController');
    }
}
