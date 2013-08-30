<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Controller;

use \Zend_Controller_Action;
use \Zend_Controller_Request_Abstract;
use \Zend_Controller_Response_Abstract;
use \Zend_Controller_Action_HelperBroker;
use \Zend_Layout;
use \Icinga\Authentication\Manager as AuthManager;
use \Icinga\Application\Benchmark;
use \Icinga\Exception;
use \Icinga\Application\Config;
use \Icinga\Web\Notification;
use \Icinga\Web\Widget\Tabs;
use \Icinga\Web\Url;

/**
 * Base class for all core action controllers
 *
 * All Icinga Web core controllers should extend this class
 */
class ActionController extends Zend_Controller_Action
{
    /**
     * True to mark this layout to not render the full layout
     *
     * @var bool
     */
    protected $replaceLayout = false;

    /**
     * If true, this controller will be shown even when no authentication is available
     * Needed mainly for the authentication controller
     *
     * @var bool
     */
    protected $requiresAuthentication = true;

    /**
     * Set true when this controller modifies the session
     *
     * otherwise the session will be written back to disk and closed before the controller
     * action is executed, leading to every modification in the session to be lost after
     * the response is submitted
     *
     * @var bool
     */
    protected $modifiesSession = false;

    /**
     * The constructor starts benchmarking, loads the configuration and sets
     * other useful controller properties
     *
     * @param Zend_Controller_Request_Abstract     $request
     * @param Zend_Controller_Response_Abstract    $response
     * @param array                                $invokeArgs Any additional invocation arguments
     */
    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs = array()
    ) {
        $this->setRequest($request)
            ->setResponse($response)
            ->_setInvokeArgs($invokeArgs);
        $this->_helper = new Zend_Controller_Action_HelperBroker($this);

        // when noInit is set (e.g. for testing), authentication and init is skipped
        if (isset($invokeArgs['noInit'])) {
            return;
        }

        if ($this->determineAuthenticationState() === true) {
            $this->view->tabs = new Tabs();
            $this->init();
        } else {
            $this->redirectToLogin();
        }
    }

    protected function determineAuthenticationState(array $invokeArgs = array())
    {
        if (!$this->requiresAuthentication) {
            return true;
        }


        return AuthManager::getInstance(
            null,
            array('writeSession' => $this->modifiesSession)
        )->isAuthenticated();
    }
    /**
     * Return the tabs
     *
     * @return Tabs
     */
    public function getTabs()
    {
        return $this->view->tabs;
    }


    /**
     * Translate the given string with the global translation catalog
     *
     * @param   string $string The string that should be translated
     *
     * @return  string
     */
    public function translate($string)
    {
        return t($string);
    }

    /**
     * Redirect to the login path
     */
    private function redirectToLogin()
    {
        $url = Url::fromPath('/authentication/login');
        $this->redirectNow($url->getRelativeUrl());
    }

    /**
    *  Redirect to a specific url, updating the browsers URL field
    *
    *  @param Url|string $url The target to redirect to
    **/
    public function redirectNow($url)
    {
        if ($url instanceof Url) {
            $url = $url->getRelativeUrl();
        } else {
            $url = Url::fromPath($url)->getRelativeUrl();
        }
        $this->_helper->Redirector->gotoUrlAndExit($url);
    }

    /**
     * Detect whether the current request requires changes in the layout and apply them before rendering
     *
     * @see Zend_Controller_Action::postDispatch()
     */
    public function postDispatch()
    {
        Benchmark::measure('Action::postDispatch()');

        if ($this->_request->isXmlHttpRequest()) {
            if ($this->replaceLayout || $this->_getParam('_render') === 'body') {
                $this->_helper->layout()->setLayout('body');
                header('X-Icinga-Target: body');
            } else {
                $this->_helper->layout()->setLayout('inline');
            }
        }
    }

    /**
     * Try to call compatible methods from older zend versions
     *
     * Methods like getParam and redirect are _getParam/_redirect in older Zend versions (which reside for example
     * in Debian Wheezy). Using those methods without the "_" causes the application to fail on those platforms, but
     * using the version with "_" forces us to use deprecated code. So we try to catch this issue by looking for methods
     * with the same name, but with a "_" prefix prepended.
     *
     * @param string $name  The method name to check
     * @param array $params The method parameters
     */
    public function __call($name, $params)
    {
        $deprecatedMethod = '_'.$name;

        if (method_exists($this, $deprecatedMethod)) {
            return call_user_func_array(array($this, $deprecatedMethod), $params);
        }
        return parent::__call($name, $params);
    }
}
