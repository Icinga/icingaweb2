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

namespace Icinga\Web\Controller;

use Exception;
use Zend_Controller_Action;
use Zend_Controller_Request_Abstract;
use Zend_Controller_Response_Abstract;
use Zend_Controller_Action_HelperBroker;
use Icinga\Authentication\Manager as AuthManager;
use Icinga\Application\Benchmark;
use Icinga\Application\Config;
use Icinga\Util\Translator;
use Icinga\Web\Widget\Tabs;
use Icinga\Web\Url;
use Icinga\Web\Notification;
use Icinga\File\Pdf;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Session;
use Icinga\Session\SessionNamespace;
use Icinga\Exception\NotReadableError;

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
     * Whether the controller requires configuration
     *
     * @var bool
     */
    protected $requiresConfiguration = true;

    private $config;

    private $configs = array();

    private $autorefreshInterval;

    private $noXhrBody = false;

    private $reloadCss = false;

    private $windowId;

    protected $isRedirect = false;

    // TODO: This would look better if we had a ModuleActionController
    public function Config($file = null)
    {
        if ($this->config === null) {
            $module = $this->getRequest()->getModuleName();
            if ($module === 'default') {
                if ($file === null) {
                    $this->config = Config::app();
                } else {
                    $this->config = Config::app($file);
                }
            } else {
                if ($file === null) {
                    $this->config = Config::module($module);
                } else {
                    $this->config = Config::module($module, $file);
                }
            }
        }
        return $this->config;
    }

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
        $this->_helper->addPath('../application/controllers/helpers');

        if ($this->_request->isXmlHttpRequest()) {
            $this->windowId = $this->_request->getHeader('X-Icinga-WindowId', null);
        }

        if ($this->requiresConfig() === false) {
            if ($this->requiresLogin() === false) {
                $this->view->tabs = new Tabs();
                $this->init();
            } else {
                $url = $this->getRequestUrl();
                if ($url === 'default/index/index') {
                    // TODO: We need our own router :p
                    $url = 'dashboard';
                }
                $this->redirectToLogin($url);
            }
        } else {
            $this->redirectNow(Url::fromPath('install'));
        }
    }

    protected function getWindowId()
    {
        if ($this->windowId === null) {
            return 'undefined';
        }
        return $this->windowId;
    }

    protected function generateWindowId()
    {
        $letters = 'abcefghijklmnopqrstuvwxyz';
        $this->windowId = substr(str_shuffle($letters), 0, 12);
        return $this->windowId;
    }

    protected function reloadCss()
    {
        $this->reloadCss = true;
    }

    /**
     * Return a window-aware session by using the given prefix
     *
     * @param   string      $prefix     The prefix to use
     * @param   bool        $reset      Whether to reset any existing session-data
     *
     * @return  SessionNamespace
     */
    public function getWindowSession($prefix, $reset = false)
    {
        $session = Session::getSession();
        $windowId = $this->getWindowId();

        $identifier = $prefix . '_' . $windowId;
        if ($reset && $session->hasNamespace($identifier)) {
            $session->removeNamespace($identifier);
        }
        $namespace = $session->getNamespace($identifier);

        if (!$reset && $windowId !== 'undefined' && $session->hasNamespace($prefix . '_undefined')) {
            // We do not have any window-id on the very first request. Now we add all values from the
            // namespace, that has been created in this case, to the new one and remove it afterwards.
            foreach ($session->getNamespace($prefix . '_undefined') as $name => $value) {
                $namespace->set($name, $value);
            }
            $session->removeNamespace($prefix . '_undefined');
        }

        return $namespace;
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
     * Check whether the controller requires configuration. That is when no configuration
     * is available and when it is possible to setup the configuration
     *
     * @return  bool
     *
     * @see     requiresConfiguration
     */
    protected function requiresConfig()
    {
        if (!$this->requiresConfiguration) {
            return false;
        }

        if (file_exists(Config::$configDir . '/setup.token')) {
            try {
                $config = Config::app()->toArray();
            } catch (NotReadableError $e) {
                return true;
            }

            return empty($config);
        } else {
            return false;
        }
    }

    /**
     * Check whether the controller requires a login. That is when the controller requires authentication and the
     * user is currently not authenticated
     *
     * @return  bool
     *
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

    protected function ignoreXhrBody()
    {
        $this->noXhrBody = true;
    }

    public function setAutorefreshInterval($interval)
    {
        if (! is_int($interval) || $interval < 1) {
            throw new ProgrammingError(
                'Setting autorefresh interval smaller than 1 second is not allowed'
            );
        }
        $this->autorefreshInterval = $interval;
    }

    /**
     * Redirect to the login path
     *
     * @param   string      $afterLogin   The action to call when the login was successful. Defaults to '/index/welcome'
     *
     * @throws  \Exception
     */
    protected function redirectToLogin($afterLogin = '/dashboard')
    {
        $url = Url::fromPath('/authentication/login');
        if ($this->getRequest()->isXmlHttpRequest()) {
            $url->setParam('_render', 'layout');
/*
            $this->_response->setHttpResponseCode(401);
            $this->_helper->json(
                array(
                    'exception'     => 'You are not logged in',
                    'redirectTo'    => Url::fromPath('/authentication/login')->getAbsoluteUrl()
                )
            );
*/
        }
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
         // TODO: We should NOT fiddle with Querystring here in the middle of nowhere
         if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '') {
             return $base . '?' . $_SERVER['QUERY_STRING'];
         }
         return $base;
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
        $this->_helper->Redirector->gotoUrlAndExit(preg_replace('~&amp;~', '&', $url));
        $this->isRedirect = true;
    }

    /**
     * Detect whether the current request requires changes in the layout and apply them before rendering
     *
     * @see Zend_Controller_Action::postDispatch()
     */
    public function postDispatch()
    {
        Benchmark::measure('Action::postDispatch()');

        $layout = $this->_helper->layout();
        // $this->addModuleContainer();
        $isXhr = $this->_request->isXmlHttpRequest();
        $layout->moduleName = $this->_request->getModuleName();
        if ($layout->moduleName === 'default') {
            $layout->moduleName = false;
        } elseif ($isXhr) {
            header('X-Icinga-Module: ' . $layout->moduleName);
        }

        if ($user = $this->getRequest()->getUser()) {
            // Cast preference app.showBenchmark to bool because preferences loaded from a preferences storage are
            // always strings
            if ((bool) $user->getPreferences()->get('app.showBenchmark', false) === true) {
                Benchmark::measure('Response ready');
                $layout->benchmark = $this->renderBenchmark();
            }
        }

        if ($this->_request->getParam('format') === 'pdf') {
            $layout->setLayout('pdf');
            $this->sendAsPdf();
            exit;
        }

        if ($isXhr) {
            $layout->setLayout('inline');
        }

        $notifications = Notification::getInstance();
        if ($isXhr && ! $this->isRedirect && $notifications->hasMessages()) {
            foreach ($notifications->getMessages() as $m) {
                header('X-Icinga-Notification: ' . $m->type . ' ' . $m->message);
            }
        }

        if ($isXhr && ($this->reloadCss || $this->getParam('_reload') === 'css')) {
            header('X-Icinga-CssReload: now');
        }

        if ($isXhr && $this->noXhrBody) {
            header('X-Icinga-Container: ignore');
            return;
        }

        if ($isXhr && $this->getWindowId() === 'undefined') {
            header('X-Icinga-WindowId: ' . $this->generateWindowId());
        }

        if ($this->view->title) {
            if (preg_match('~[\r\n]~', $this->view->title)) {
                // TODO: Innocent exception and error log for hack attempts
                throw new Exception('No way, guy');
            }
            header('X-Icinga-Title: ' . $this->view->title . ' :: Icinga Web');
        }
        // TODO: _render=layout?
        if ($this->getParam('_render') === 'layout') {
            $layout->setLayout('body');
            header('X-Icinga-Container: layout');
        }
        if ($this->autorefreshInterval !== null) {
            header('X-Icinga-Refresh: ' . $this->autorefreshInterval);
        }
    }

    protected function addModuleContainer()
    {
        $module_name = $this->_request->getModuleName();
        $this->_helper->layout()->moduleStart =
        '<div class="icinga-module module-'
          . $module_name
          . '" data-icinga-module="' . $module_name . '">'
          . "\n"
          ;
        $this->_helper->layout()->moduleEnd = "</div>\n";
    }

    protected function sendAsPdf()
    {
        $pdf = new Pdf();
        $pdf->renderControllerAction($this);
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
// @codeCoverageIgnoreEnd
