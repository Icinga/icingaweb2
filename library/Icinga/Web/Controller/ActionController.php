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

use \Icinga\Authentication\Manager as AuthManager;
use \Icinga\Application\Benchmark;
use \Icinga\Exception;
use \Icinga\Application\Config;
use \Icinga\Web\Notification;
use \Zend_Layout as ZfLayout;
use \Zend_Controller_Action as ZfController;
use \Zend_Controller_Request_Abstract as ZfRequest;
use \Zend_Controller_Response_Abstract as ZfResponse;
use \Zend_Controller_Action_HelperBroker as ZfActionHelper;

/*
 * @TODO(el): There was a note from tg that the following line is temporary. Ask him why.
 */
use Icinga\File\Pdf;

/**
 * Base class for all core action controllers
 *
 * All Icinga Web core controllers should extend this class
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class ActionController extends ZfController
{
    /**
     * The Icinga Config object is available in all controllers. This is the
     * modules config for module action controllers.
     * @var Config
     */
    protected $config;

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

    // @TODO(el): Should be true, is/was disabled for testing purpose
    protected $handlesAuthentication = false;

    protected $modifiesSession = false;

    private $allowAccess = false;

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

        $this->module_name     = $request->getModuleName();
        $this->controller_name = $request->getControllerName();
        $this->action_name     = $request->getActionName();

        $this->loadConfig();
        $this->setRequest($request)
             ->setResponse($response)
             ->_setInvokeArgs($invokeArgs);
        $this->_helper = new ZfActionHelper($this);

        if ($this->handlesAuthentication() ||
                AuthManager::getInstance(
                    null,
                    array(
                        'writeSession' => $this->modifiesSession
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
        $this->config = Config::app();
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
     * Whether the current user has the given permission
     *
     * TODO: This has not been implemented yet
     *
     * @param  string $permission Permission name
     * @param  string $object     No idea what this should have been :-)
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
     * @return self
     */
    final protected function assertPermission($permission, $object = null)
    {
        if (! $this->hasPermission($permission, $object)) {
            // TODO: Log violation, create dedicated Exception class
            throw new \Exception('Permission denied');
        }
        return $this;
    }

    protected function preserve($key, $value = null)
    {
        if ($value === null) {
            $value = $this->_getParam($key);
        }
        if ($value !== null) {
            if (! isset($this->view->preserve)) {
                $this->view->preserve = array();
            }
            $this->view->preserve[$key] = $value;
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
        if (! $this->allowAccess) {
            $this->_request->setModuleName('default')
                ->setControllerName('authentication')
                ->setActionName('login')
                ->setDispatched(false);
            return;
        }

        $this->view->action_name = $this->action_name;
        $this->view->controller_name = $this->controller_name;
        $this->view->module_name = $this->module_name;

        // TODO(el): What is this, why do we need that here?
        $this->view->compact = $this->_request->getParam('view') === 'compact';

        Benchmark::measure(sprintf(
            'Action::preDispatched(): %s / %s / %s',
            $this->module_name,
            $this->controller_name,
            $this->action_name
        ));

        //$this->quickRedirect('/authentication/login?a=e');
    }

    public function redirectNow($url, array $params = array())
    {
        if ($url instanceof Url) {
            $url = $url->getRelativeUrl();
        }
        $this->_helper->Redirector->gotoUrlAndExit($url);
    }

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
            foreach (\Icinga\Application\Icinga::app()->getModuleManager()->getLoadedModules() as $name => $module) {
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
            $html = (string) $this->getResponse();
            if ($this->module_name !== null) {
                $html = '<div class="icinga-module module-'
          . $this->module_name
          . '">'
          . "\n"
          . $html
          . '</div>';
            }

            $html = '<style>' . $css . '</style>' . $html;

            //$html .= $this->view->action('services', 'list', 'monitoring', array('limit' => 10));
//            $html = preg_replace('~icinga-module.module-bpapp~', 'csstest', $html);
// echo $html; exit;
            $pdf = new Pdf();
            $pdf->AddPage();
            $pdf->setFontSubsetting(false);
            $pdf->writeHTML($html); //0, 0, '', '', $html, 0, 1, 0, true, '', true);

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

        if (AuthManager::getInstance()->getSession()->get('show_benchmark')) {
            Benchmark::measure('Response ready');
            $this->_helper->layout()->benchmark = $this->renderBenchmark();
        }

    }
}
