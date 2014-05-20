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

// namespace Icinga\Application\Controllers;

use Icinga\Web\Controller\ActionController;
use Icinga\Application\Icinga;

/**
 * Application wide controller for displaying exceptions
 */
class ErrorController extends ActionController
{
    protected $requiresAuthentication = false;

    /**
     * Display exception
     */
    public function errorAction()
    {
        $error      = $this->_getParam('error_handler');
        $exception  = $error->exception;
        switch ($error->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                $modules = Icinga::app()->getModuleManager();
                $path = ltrim($this->_request->get('PATH_INFO'), '/');
                $path = preg_split('~/~', $path);
                $path = array_shift($path);
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = $this->translate('Page not found.');
                if ($modules->hasInstalled($path) && ! $modules->hasEnabled($path)) {
                    $this->view->message .= ' ' . sprintf(
                        $this->translate('Enabling the "%s" module might help!'),
                        $path
                    );
                }

                break;
            default:
                $title = preg_replace('/\r?\n.*$/s', '', $exception->getMessage());
                $this->getResponse()->setHttpResponseCode(500);
                $this->view->title = 'Server error: ' . $title;
                $this->view->message = $exception->getMessage();
                if ($this->getInvokeArg('displayExceptions') == true) {
                    $this->view->stackTrace = $exception->getTraceAsString();
                }
                break;
        }
        $this->view->request = $error->request;
    }
}
// @codeCoverageIgnoreEnd
