<?php
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

namespace Icinga\Web;

use Icinga\Authentication\Manager;
use Icinga\Application\Benchmark;
use Icinga\Exception;
use Icinga\Application\Config;
use Icinga\Pdf\File;
use Icinga\Web\Notification;
use Zend_Layout as ZfLayout;
use Zend_Controller_Action as ZfController;
use Zend_Controller_Request_Abstract as ZfRequest;
use Zend_Controller_Response_Abstract as ZfResponse;
use Zend_Controller_Action_HelperBroker as ZfActionHelper;

/**
 * Base class for all core action controllers
 *
 * All Icinga Web core controllers should extend this class
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @package Icinga\Web
 */
class ActionController extends ZfController
{
    /**
     * The Icinga Config object is available in all controllers. This is the
     * modules config for module action controllers.
     *
     * @var Config
     */
    protected $config;

    /**
     * @var bool
     */
    protected $replaceLayout = false;

    /**
     * The current module name. TODO: Find out whether this shall be null for
     * non-module actions
     *
     * @var string
     */
    protected $module_name;

    /**
     * The current controller name
     *
     * @var string
     */
    protected $controller_name;

    /**
     * The current action name
     *
     * @var string
     */
    protected $action_name;

    /**
     * @var bool
     */
    protected $handlesAuthentication = false;

    /**
     * @var bool
     */
    protected $modifiesSession = false;

    /**
     * @var bool
     */
    protected $allowAccess = false;

    /**
     * The constructor starts benchmarking, loads the configuration and sets
     * other useful controller properties
     *
     * @param ZfRequest $request
     * @param ZfResponse $response
     * @param array $invokeArgs Any additional invocation arguments
     */
    public function __construct(
        ZfRequest $request,
        ZfResponse $response,
        array $invokeArgs = array()
    ) {
        Benchmark::measure('Action::__construct()');
        $this->module_name = $request->getModuleName();
        $this->controller_name = $request->getControllerName();
        $this->action_name = $request->getActionName();

        $this->loadConfig();
        $this->setRequest($request)
            ->setResponse($response)
            ->_setInvokeArgs($invokeArgs);
        $this->_helper = new ZfActionHelper($this);

        /*
         * --------------------------------------------
         * Authentication is disabled to test bootstrap
         * --------------------------------------------
         *
         * @todo remove this!
         */


        if ($this->handlesAuthentication() ||
                Manager::getInstance(
                    null,
                    array(
                        "writeSession" => $this->modifiesSession
                    )
                )->isAuthenticated()
        ) {
            $this->allowAccess = true;
            $this->init();
        }
    }

    /**
     * This is where the configuration is going to be loaded
     *
     * @return void
     */
    protected function loadConfig()
    {
        $this->config = Config::getInstance();
    }

    /**
     * Translates the given string with the global translation catalog
     *
     * @param  string $string The string that should be translated
     *
     * @return string
     */
    public function translate($string)
    {
        return t($string);
    }

    /**
     * Helper function creating a new widget
     *
     * @param  string $name       The widget name
     * @param array|string $properties Optional widget properties
     *
     * @return Widget\AbstractWidget
     */
    public function widget($name, $properties = array())
    {
        return Widget::create($name, $properties);
    }

    /**
     * Whether the current user has the given permission
     *
     * TODO: This has not been implemented yet
     *
     * @param $uri
     * @param  string $permission Permission name
     * @internal param string $object No idea what this should have been :-)
     *
     * @return bool
     */
    final protected function hasPermission($uri, $permission = 'read')
    {
        return true;
    }

    /**
     * Assert the current user has the given permission
     *
     * TODO: This has not been implemented yet
     *
     * @param  string $permission Permission name
     * @param  string $object     No idea what this should have been :-)
     *
     * @throws \Exception
     * @return self
     */
    final protected function assertPermission($permission, $object = null)
    {
        if (!$this->hasPermission($permission, $object)) {
            // TODO: Log violation, create dedicated Exception class
            throw new \Exception('Permission denied');
        }
        return $this;
    }

    /**
     * Our benchmark wants to know when we started our dispatch loop
     *
     * @return void
     */
    public function preDispatch()
    {
        Benchmark::measure('Action::preDispatch()');

        if (!$this->allowAccess) {
            $this->_request->setModuleName('default')
                ->setControllerName('authentication')
                ->setActionName('login')
                ->setDispatched(false);
            return;
        }

        $this->view->action_name = $this->action_name;
        $this->view->controller_name = $this->controller_name;
        $this->view->module_name = $this->module_name;

        //$this->quickRedirect('/authentication/login?a=e');
    }

    /**
     * @param $url
     * @param array $params
     */
    public function redirectNow($url, array $params = array())
    {
        $this->_helper->Redirector->gotoUrlAndExit($url);
    }

    /**
     * @return bool
     */
    public function handlesAuthentication()
    {
        return $this->handlesAuthentication;
    }

    /**
     * Render our benchmark
     *
     * @return string
     */
    protected function renderBenchmark()
    {
        return '<pre class="benchmark">'
        . Benchmark::renderToHtml()
        . '</pre>';
    }

    /**
     * After dispatch happend we are going to do some automagic stuff
     *
     * - Benchmark is completed and rendered
     * - Notifications will be collected here
     * - Layout is disabled for XHR requests
     * - TODO: Headers with required JS and other things will be created
     *   for XHR requests
     *
     * @return void
     */
    public function postDispatch()
    {
        Benchmark::measure('Action::postDispatch()');


        // TODO: Move this elsewhere, this is just an ugly test:
        if ($this->_request->getParam('filetype') === 'pdf') {

            // Snippet stolen from less compiler in public/css.php:

            require_once 'vendor/lessphp/lessc.inc.php';
            $less = new \lessc;
            $cssdir = dirname(ICINGA_LIBDIR) . '/public/css';
            // TODO: We need a way to retrieve public dir, even if located elsewhere

            $css = $less->compileFile($cssdir . '/pdfprint.less');
            /*
                        foreach ($app->moduleManager()->getLoadedModules() as $name => $module) {
                            if ($module->hasCss()) {
                                $css .= $less->compile(
                                    '.icinga-module.module-'
                                    . $name
                                    . " {\n"
                                    . file_get_contents($module->getCssFilename())
                                    . "}\n\n"
                                );
                            }
                        }
            */

            // END of CSS test

            $this->render(
                null,
                $this->_helper->viewRenderer->getResponseSegment(),
                $this->_helper->viewRenderer->getNoController()
            );
            $html = '<style>' . $css . '</style>' . (string)$this->getResponse();

            $pdf = new File();
            $pdf->AddPage();
            $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
            $pdf->Output('docs.pdf', 'I');
            exit;
        }
        // END of PDF test

        if ($this->_request->isXmlHttpRequest()) {
            if ($this->replaceLayout || $this->_getParam('_render') === 'body') {
                $this->_helper->layout()->setLayout('just-the-body');
                header('X-Icinga-Target: body');
            } else {
                $this->_helper->layout()->setLayout('inline');
            }
        }

        $notification = Notification::getInstance();
        if ($notification->hasMessages()) {
            $nhtml = '<ul class="notification">';
            foreach ($notification->getMessages() as $msg) {
                $nhtml .= '<li>['
                    . $msg->type
                    . '] '
                    . htmlspecialchars($msg->message);
            }
            $nhtml .= '</ul>';
            $this->getResponse()->append('notification', $nhtml);
        }

        /*if (Session::getInstance()->show_benchmark) {
            Benchmark::measure('Response ready');
            $this->getResponse()->append('benchmark', $this->renderBenchmark());
        }*/

    }
}
