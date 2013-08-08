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
use \Icinga\Web\Widget\Tabs;
use \Zend_Layout as ZfLayout;
use \Zend_Controller_Action as ZfController;
use \Zend_Controller_Request_Abstract as ZfRequest;
use \Zend_Controller_Response_Abstract as ZfResponse;
use \Zend_Controller_Action_HelperBroker as ZfActionHelper;

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
    protected $handlesAuthentication = false;

    /**
    * Set true when this controller modifies the session.
    *
    * otherwise the session will be written back to disk and closed before the controller 
    * action is executed, leading to every modification in the session to be lost after
    * the response is submitted
    *
    * @var bool 
    */
    protected $modifiesSession = false;

    /**
    * True if authentication suceeded, otherwise false
    *
    * @var bool
    **/
    protected $allowAccess = false;


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

        $this->setRequest($request)
             ->setResponse($response)
             ->_setInvokeArgs($invokeArgs);
        $this->_helper = new ZfActionHelper($this);

        if ($this->handlesAuthentication ||
                AuthManager::getInstance(
                    null,
                    array(
                        'writeSession' => $this->modifiesSession
                    )
                )->isAuthenticated()
        ) {
            $this->allowAccess = true;
            $this->view->tabs = new Tabs();
            $this->init();
        }
    }

    /**
    * Return the @see \Icinga\Widget\Web\Tabs of this view
    *
    * @return Tabs
    **/
    public function getTabs()
    {
        return $this->view->tabs;
    }


    /**
     * Translate the given string with the global translation catalog
     *
     * @param  string $string   The string that should be translated
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
     * TODO: This has not been implemented yet (Feature #4111)
     *
     * @return bool
     */
    final protected function hasPermission($uri)
    {
        return true;
    }

    /**
     * Assert the current user has the given permission
     *
     * TODO: This has not been implemented yet (Feature #4111)
     * 
     * @return self
     */
    final protected function assertPermission()
    {
        return $this;
    }

    private function redirectToLogin()
    {
        $this->_request->setModuleName('default')
            ->setControllerName('authentication')
            ->setActionName('login')
            ->setDispatched(false);
    }

    /**
     * Prepare action execution by testing for correct permissions and setting shortcuts
     *
     * @return void
     */
    public function preDispatch()
    {
        Benchmark::measure('Action::preDispatch()');
        if (! $this->allowAccess) {
            return $this->redirectToLogin();
        }

        $this->view->action_name = $this->action_name;
        $this->view->controller_name = $this->controller_name;
        $this->view->module_name = $this->module_name;

        Benchmark::measure(
            sprintf(
                'Action::preDispatched(): %s / %s / %s',
                $this->module_name,
                $this->controller_name,
                $this->action_name
            )
        );
    }

    /**
    *  Redirect to a specific url, updating the browsers URL field
    *  
    *  @param Url|string $url       The target to redirect to
    **/
    public function redirectNow($url)
    {
        if ($url instanceof Url) {
            $url = $url->getRelativeUrl();
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
                $this->_helper->layout()->setLayout('just-the-body');
                header('X-Icinga-Target: body');
            } else {
                $this->_helper->layout()->setLayout('inline');
            }
        }
    }
}
