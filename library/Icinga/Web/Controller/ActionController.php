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

use \Exception;
use \Zend_Controller_Action;
use \Zend_Controller_Request_Abstract;
use \Zend_Controller_Response_Abstract;
use \Zend_Controller_Action_HelperBroker;
use Icinga\Authentication\Manager as AuthManager;
use Icinga\Application\Benchmark;
use Icinga\Util\Translator;
use Icinga\Web\Widget\Tabs;
use Icinga\Web\Url;

/**
 * Base class for all core action controllers
 *
 * All Icinga Web core controllers should extend this class
 */
class ActionController extends Zend_Controller_Action
{
    /**
     * Whether the controller requires the user to be authenticated
     *
     * @var bool
     */
    protected $requiresAuthentication = true;

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

    /**
     * Return restriction information for an eventually authenticated user
     *
     * @param  string  $name Permission name
     * @return Array
     */
    public function getRestrictions($name)
    {
        return AuthManager::getInstance()->getRestrictions($name);
    }

    /**
     * Whether the user currently authenticated has the given permission
     *
     * @param  string  $name Permission name
     * @return bool
     */
    public function hasPermission($name)
    {
        return AuthManager::getInstance()->hasPermission($name);
    }

    /**
     * Throws an exception if user lacks the given permission
     *
     * @param  string  $name Permission name
     * @throws Exception
     */
    public function assertPermission($name)
    {
        if (! AuthManager::getInstance()->hasPermission($name)) {
            // TODO: Shall this be an Auth Exception? Or a 404?
            throw new Exception(sprintf('Auth error, no permission for "%s"', $name));
        }
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

        return !AuthManager::getInstance()->isAuthenticated();
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
     * Translate a string
     *
     * Autoselects the module domain, if any, and falls back to the global one if no translation could be found.
     *
     * @param   string  $text   The string to translate
     *
     * @return  string          The translated string
     */
    public function translate($text)
    {
        $module = $this->getRequest()->getModuleName();
        $domain = $module === 'default' ? 'icinga' : $module;
        return Translator::translate($text, $domain);
    }

    /**
     * Redirect to the login path
     *
     * @param   string      $afterLogin   The action to call when the login was successful. Defaults to '/index/welcome'
     *
     * @throws  \Exception
     */
    protected function redirectToLogin($afterLogin = '/index')
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_response->setHttpResponseCode(401);
            $this->_helper->json(array(
                'exception'     => 'You are not logged in',
                'redirectTo'    => Url::fromPath('/authentication/login')->getAbsoluteUrl()
            ));
        }
        $url = Url::fromPath('/authentication/login');
        $url->setParam('redirect', $afterLogin);
        $this->redirectNow($url);
    }

    /**
     * Return the URI that can be used to request the current action
     *
     * @return string   return the path to this action: <Module>/<Controller>/<Action>?<Query>
     */
    public function getRequestUrl()
    {
         $base = $this->_request->getModuleName() . '/' .
            $this->_request->getControllerName() . '/' .
            $this->_request->getActionName();
         return $_SERVER['QUERY_STRING'] !== '' ? $base . '?' . $_SERVER['QUERY_STRING'] : $base;
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
            $target = ($this->getParam('render') === 'detail') ? 'inline' : 'body';
            if ($target !== 'inline') {
                $target = ($this->getParam('view') === 'compact') ? 'inline' : 'body';
            }
            $this->_helper->layout()->setLayout($target);
        }
        if ($user = $this->getRequest()->getUser()) {
            if ($user->getPreferences()->get('app.showBenchmark') === true) {
                Benchmark::measure('Response ready');
                $this->_helper->layout()->benchmark = $this->renderBenchmark();
            }
        }
    }

    /**
     * Render the benchmark
     *
     * @return string Benchmark HTML
     */
    protected function renderBenchmark()
    {
        return Benchmark::renderToHtml();
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

        return parent::__call($name, $params);
    }
}
