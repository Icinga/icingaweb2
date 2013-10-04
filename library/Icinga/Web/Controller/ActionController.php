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

use \Exception;
use \Zend_Controller_Action;
use \Zend_Controller_Request_Abstract;
use \Zend_Controller_Front;
use \Zend_Controller_Response_Abstract;
use \Zend_Controller_Action_HelperBroker;
use \Zend_Layout;
use \Icinga\Authentication\Manager as AuthManager;
use \Icinga\Application\Benchmark;
use \Icinga\Application\Config;
use \Icinga\Web\Notification;
use \Icinga\Web\Widget\Tabs;
use \Icinga\Web\Url;
use \Icinga\Web\Request;

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
     * Whether the controller requires the user to be authenticated
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

        if ($this->requiresLogin() === false) {
            $this->view->tabs = new Tabs();
            $this->init();
        } else {
            $this->redirectToLogin($this->getRequestUrl());
        }
    }

    private function dispatchDetailView($url)
    {
        // strip the base URL from the detail $url
        $url = substr($url, strlen($this->getRequest()->getBaseUrl()));
        // the host is mandatory, but ignored in Zend
        $req = new Request('http://ignoredhost/' . $url);

        $router = Zend_Controller_Front::getInstance()->getRouter();
        $router->route($req);
        $detailHtml = $this->view->action($req->getActionName(), $req->getControllerName(), $req->getModuleName());
        $this->_helper->layout->assign('detailContent', $detailHtml);
        $this->_helper->layout->assign('detailClass', 'col-sm-12 col-xs-12 col-md-12 col-lg-6');
        $this->_helper->layout->assign('mainClass', 'col-sm-12 col-xs-12 col-md-12 col-lg-6');
    }

    /**
     * Check whether the controller requires a login. That is when the controller requires authentication and the
     * user is currently not authenticated
     *
     * @return  bool
     * @see     requiresAuthentication
     */
    protected function requiresLogin()
    {
        if (!$this->requiresAuthentication) {
            return false;
        }

        return !AuthManager::getInstance(
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
     *
     * @param   string      $afterLogin   The action to call when the login was successful. Defaults to '/index/welcome'
     *
     * @throws  \Exception
     */
    protected function redirectToLogin($afterLogin = '/index/welcome')
    {
        if ($this->getRequest()->isXmlHttpRequest()) {

            $this->getResponse()->setHttpResponseCode(401);
            $this->getResponse()->sendHeaders();
            throw new Exception("You are not logged in");
        }
        $url = Url::fromPath('/authentication/login');
        $url->setParam('redirect', $afterLogin);
        $this->redirectNow($url->getRelativeUrl());
    }

    /**
     * Return the URI that can be used to request the current action
     *
     * @return string   return the path to this action: <Module>/<Controller>/<Action>
     */
    public function getRequestUrl()
    {
        return $this->_request->getModuleName() . '/' .
         $this->_request->getControllerName() . '/' .
         $this->_request->getActionName();
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
            $this->_helper->layout()->setLayout('body');
        }

        if ($this->getParam('detail', false)) {
            $detail = $this->getParam('detail');

            // Zend uses the GET variables when calling getParam, therefore we have to persist the params,
            // clear the $_GET array, call the detail view with the url set in $detail and afterwards recreate
            // the $_GET array. If this is not done the following issues occur:
            //
            // - A stackoverflow issue due to infinite nested calls of buildDetailView (as the detailview has the same
            //   postDispatch method) when 'detail' is not set to null
            //
            // - Params (like filters in the URL) from the detail view would be applied on all links of the master view
            //   as those would be in the $_GET array after building the detail view. E.g. if you have a grid in the
            //   master and a detail view filtering showing one host in detail, the pagination links of the grid would
            //   contain the host filter of the detail view
            //
            $params = $_GET;
            $_GET['detail'] = null;
            $this->dispatchDetailView($detail);
            $_GET = $params;
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
     * @param   string  $name   The method name to check
     * @param   mixed   $params The method parameters
     * @return  mixed           Anything the method returns
     */
    public function __call($name, $params)
    {
        $deprecatedMethod = '_' . $name;

        if (method_exists($this, $deprecatedMethod)) {
            return call_user_func_array(array($this, $deprecatedMethod), $params);
        }

        parent::__call($name, $params);

        return null;
    }
}
