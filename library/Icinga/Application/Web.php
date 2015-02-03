<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Application;

require_once __DIR__ . '/ApplicationBootstrap.php';

use Icinga\Authentication\Manager as AuthenticationManager;
use Icinga\Authentication\Manager;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotReadableError;
use Icinga\Application\Logger;
use Icinga\Util\TimezoneDetect;
use Icinga\Web\Request;
use Icinga\Web\Response;
use Icinga\Web\View;
use Icinga\Web\Session\Session as BaseSession;
use Icinga\Web\Session;
use Icinga\User;
use Icinga\Util\Translator;
use Icinga\Util\DateTimeFactory;
use DateTimeZone;
use Exception;
use Zend_Layout;
use Zend_Paginator;
use Zend_View_Helper_PaginationControl;
use Zend_Controller_Action_HelperBroker as ActionHelperBroker;
use Zend_Controller_Router_Route;
use Zend_Controller_Front;

/**
 * Use this if you want to make use of Icinga functionality in other web projects
 *
 * Usage example:
 * <code>
 * use Icinga\Application\EmbeddedWeb;
 * EmbeddedWeb::start();
 * </code>
 */
class Web extends ApplicationBootstrap
{
    /**
     * View object
     *
     * @var View
     */
    private $viewRenderer;

    /**
     * Zend front controller instance
     *
     * @var Zend_Controller_Front
     */
    private $frontController;

    /**
     * Request object
     *
     * @var Request
     */
    private $request;

    /**
     * Session object
     *
     * @var BaseSession
     */
    private $session;

    /**
     * User object
     *
     * @var User
     */
    private $user;

    /**
     * Identify web bootstrap
     *
     * @var bool
     */
    protected $isWeb = true;

    /**
     * Initialize all together
     *
     * @return self
     */
    protected function bootstrap()
    {
        return $this
            ->setupZendAutoloader()
            ->setupLogging()
            ->setupErrorHandling()
            ->loadConfig()
            ->setupResourceFactory()
            ->setupSession()
            ->setupUser()
            ->setupTimezone()
            ->setupLogger()
            ->setupInternationalization()
            ->setupRequest()
            ->setupZendMvc()
            ->setupFormNamespace()
            ->setupModuleManager()
            ->loadSetupModuleIfNecessary()
            ->loadEnabledModules()
            ->setupRoute()
            ->setupPagination();
    }

    /**
     * Prepare routing
     *
     * @return self
     */
    private function setupRoute()
    {
        $this->frontController->getRouter()->addRoute(
            'module_javascript',
            new Zend_Controller_Router_Route(
                'js/components/:module_name/:file',
                array(
                    'controller' => 'static',
                    'action'     => 'javascript'
                )
            )
        );

        return $this;
    }

    /**
     * Getter for frontController
     *
     * @return Zend_Controller_Front
     */
    public function getFrontController()
    {
        return $this->frontController;
    }

    /**
     * Getter for view
     *
     * @return View
     */
    public function getViewRenderer()
    {
        return $this->viewRenderer;
    }

    /**
     * Dispatch public interface
     */
    public function dispatch()
    {
        $this->frontController->dispatch($this->request, new Response());
    }

    /**
     * Prepare Zend MVC Base
     *
     * @return self
     */
    private function setupZendMvc()
    {
        // TODO: Replace Zend_Application:
        Zend_Layout::startMvc(
            array(
                'layout'     => 'layout',
                'layoutPath' => $this->getApplicationDir('/layouts/scripts')
            )
        );

        $this->setupFrontController();
        $this->setupViewRenderer();

        return $this;
    }

    /**
     * Create user object
     *
     * @return  self
     */
    private function setupUser()
    {
        $authenticationManager = AuthenticationManager::getInstance();

        if ($authenticationManager->isAuthenticated() === true) {
            $this->user = $authenticationManager->getUser();
        }

        return $this;
    }

    /**
     * Initialize a session provider
     *
     * @return  self
     */
    private function setupSession()
    {
        $this->session = Session::create();
        return $this;
    }

    /**
     * Inject dependencies into request
     *
     * @return self
     */
    private function setupRequest()
    {
        $this->request = new Request();

        if ($this->user instanceof User) {
            $this->request->setUser($this->user);
        }

        return $this;
    }

    /**
     * Instantiate front controller
     *
     * @return self
     */
    private function setupFrontController()
    {
        $this->frontController = Zend_Controller_Front::getInstance();

        $this->frontController->setRequest($this->request);

        $this->frontController->setControllerDirectory($this->getApplicationDir('/controllers'));

        $this->frontController->setParams(
            array(
                'displayExceptions' => true
            )
        );

        return $this;
    }

    /**
     * Register helper paths and views for renderer
     *
     * @return self
     */
    private function setupViewRenderer()
    {
        /** @var \Zend_Controller_Action_Helper_ViewRenderer $view */
        $view = ActionHelperBroker::getStaticHelper('viewRenderer');
        $view->setView(new View());

        $view->view->addHelperPath($this->getApplicationDir('/views/helpers'));

        $view->view->setEncoding('UTF-8');
        $view->view->headTitle()->prepend($this->config->get('global', 'project', 'Icinga'));

        $view->view->headTitle()->setSeparator(' :: ');

        $this->viewRenderer = $view;

        return $this;
    }

    /**
     * Configure pagination settings
     *
     * @return self
     */
    private function setupPagination()
    {

        Zend_Paginator::addScrollingStylePrefixPath(
            'Icinga_Web_Paginator_ScrollingStyle',
            'Icinga/Web/Paginator/ScrollingStyle'
        );

        Zend_Paginator::setDefaultScrollingStyle('SlidingWithBorder');
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            array('mixedPagination.phtml', 'default')
        );

        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see ApplicationBootstrap::detectTimezone() For the method documentation.
     */
    protected function detectTimezone()
    {
        $auth = Manager::getInstance();
        if (! $auth->isAuthenticated()
            || ($timezone = $auth->getUser()->getPreferences()->getValue('icingaweb', 'timezone')) === null
        ) {
            $detect = new TimezoneDetect();
            $timezone = $detect->getTimezoneName();
        }
        return $timezone;
    }

    /**
     * Setup internationalization using gettext
     *
     * Uses the preferred user language or the configured default and system default, respectively.
     *
     * @return  self
     */
    protected function detectLocale()
    {
        $auth = Manager::getInstance();
        if (! $auth->isAuthenticated()
            || ($locale = $auth->getUser()->getPreferences()->getValue('icingaweb', 'language')) === null
            && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
        ) {
            $locale = Translator::getPreferredLocaleCode($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        }
        return $locale;
    }

    /**
     * Setup an autoloader namespace for Icinga\Forms
     *
     * @return  self
     */
    private function setupFormNamespace()
    {
        $this->getLoader()->registerNamespace(
            'Icinga\\Forms',
            $this->getApplicationDir('forms')
        );
        return $this;
    }
}
// @codeCoverageIgnoreEnd
