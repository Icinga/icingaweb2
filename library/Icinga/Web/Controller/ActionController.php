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
use Icinga\Logger\Logger;
use Icinga\Web\Request;

use Icinga\File\Pdf;
use \DOMDocument;
use Icinga\Exception\ProgrammingError;

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

    private $config;

    private $configs = array();

    private $autorefreshInterval;

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

        // when noInit is set (e.g. for testing), authentication and init is skipped
        if (isset($invokeArgs['noInit'])) {
            return;
        }

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

        if ($this->_request->isXmlHttpRequest() || $this->getParam('view') === 'compact') {
            $this->_helper->layout()->setLayout('inline');
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
            $this->_helper->layout()->setLayout('body');
            header('X-Icinga-Container: layout');
        }
        if ($this->autorefreshInterval !== null) {        
            header('X-Icinga-Refresh: ' . $this->autorefreshInterval);
        }
        if ($user = $this->getRequest()->getUser()) {
            // Cast preference app.showBenchmark to bool because preferences loaded from a preferences storage are
            // always strings
            if ((bool) $user->getPreferences()->get('app.showBenchmark', false) === true) {
                Benchmark::measure('Response ready');
                $this->_helper->layout()->benchmark = $this->renderBenchmark();
            }
        }
        if ($this->_request->getParam('format') === 'pdf' && $this->_request->getControllerName() !== 'static') {
            $this->sendAsPdfAndDie();
        }

        // Module container
        $module_name = $this->_request->getModuleName();
        $this->_helper->layout()->moduleStart =
        '<div class="icinga-module module-'
          . $module_name
          . '" data-icinga-module="' . $module_name . '">'
          . "\n"
          ;
        $this->_helper->layout()->moduleEnd = "</div>\n";
    }

    protected function sendAsPdfAndDie()
    {
        $this->_helper->layout()->setLayout('inline');
        $body = $this->view->render(
            $this->_request->getControllerName() . '/' . $this->_request->getActionName() . '.phtml'
        );
        if (!headers_sent()) {
            $css = $this->view->getHelper('action')->action('stylesheet', 'static', 'application');

            // load css fixes for pdf formatting mode
            $publicDir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
            $css .= file_get_contents($publicDir . '/css/pdf/pdf.css');

            $pdf = new PDF();
            if ($this->_request->getControllerName() === 'list') {
                switch ($this->_request->getActionName()) {
                    case 'notifications':
                        $pdf->rowsPerPage = 7;
                        break;
                    case 'comments':
                        $pdf->rowsPerPage = 7;
                        break;
                    default:
                        $pdf->rowsPerPage = 11;
                        break;
                }
            } else {
                $pdf->paginateTable = false;
            }
            $pdf->renderPage($body, $css);
            $pdf->stream(
                $this->_request->getControllerName() . '-' . $this->_request->getActionName() . '-' . time() . '.pdf'
            );
        } else {
            Logger::error('Could not send pdf-response, content already written to output.');
        }
        die();
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
