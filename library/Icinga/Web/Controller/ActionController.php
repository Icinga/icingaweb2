<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Controller;

use Exception;
use Icinga\Application\Benchmark;
use Icinga\Application\Config;
use Icinga\Authentication\Manager;
use Icinga\Exception\IcingaException;
use Icinga\Exception\ProgrammingError;
use Icinga\File\Pdf;
use Icinga\Forms\AutoRefreshForm;
use Icinga\Security\SecurityException;
use Icinga\Util\Translator;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use Icinga\Web\Url;
use Icinga\Web\UrlParams;
use Icinga\Web\Widget\Tabs;
use Icinga\Web\Window;
use Zend_Controller_Action;
use Zend_Controller_Action_HelperBroker as ActionHelperBroker;
use Zend_Controller_Request_Abstract as Request;
use Zend_Controller_Response_Abstract as Response;

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

    private $autorefreshInterval;

    private $reloadCss = false;

    private $window;

    private $rerenderLayout = false;

    private $xhrLayout = 'inline';

    /**
     * Authentication manager
     *
     * @var Manager|null
     */
    private $auth;

    /**
     * URL parameters
     *
     * @var UrlParams
     */
    protected $params;

    /**
     * The constructor starts benchmarking, loads the configuration and sets
     * other useful controller properties
     *
     * @param Request  $request
     * @param Response $response
     * @param array    $invokeArgs Any additional invocation arguments
     */
    public function __construct(
        Request $request,
        Response $response,
        array $invokeArgs = array()
    ) {
        $this->params = UrlParams::fromQueryString();

        $this->setRequest($request)
            ->setResponse($response)
            ->_setInvokeArgs($invokeArgs);
        $this->_helper = new ActionHelperBroker($this);

        $this->handlerBrowserWindows();
        $this->view->translationDomain = 'icinga';
        $this->_helper->layout()->isIframe = $request->getUrl()->shift('isIframe');
        $this->_helper->layout()->moduleName = false;

        $this->view->compact = $request->getParam('view') === 'compact';
        if ($this->rerenderLayout = $request->getUrl()->shift('renderLayout')) {
            $this->xhrLayout = 'body';
        }

        if ($this->requiresLogin()) {
            $this->redirectToLogin(Url::fromRequest());
        }

        $this->view->tabs = new Tabs();
        $this->prepareInit();
        $this->init();
    }

    /**
     * Prepare controller initialization
     *
     * As it should not be required for controllers to call the parent's init() method, base controllers should use
     * prepareInit() in order to prepare the controller initialization.
     *
     * @see \Zend_Controller_Action::init() For the controller initialization method.
     */
    protected function prepareInit()
    {
    }

    /**
     * Get the authentication manager
     *
     * @return Manager
     */
    public function Auth()
    {
        if ($this->auth === null) {
            $this->auth = Manager::getInstance();
        }
        return $this->auth;
    }

    /**
     * Whether the current user has the given permission
     *
     * @param   string  $permission Name of the permission
     *
     * @return  bool
     */
    public function hasPermission($permission)
    {
        return $this->Auth()->hasPermission($permission);
    }

    /**
     * Assert that the current user has the given permission
     *
     * @param   string  $permission     Name of the permission
     *
     * @throws  SecurityException       If the current user lacks the given permission
     */
    public function assertPermission($permission)
    {
        if (! $this->Auth()->hasPermission($permission)) {
            throw new SecurityException('No permission for %s', $permission);
        }
    }

    public function Config($file = null)
    {
        if ($file === null) {
            return Config::app();
        } else {
            return Config::app($file);
        }
    }

    public function Window()
    {
        if ($this->window === null) {
            $this->window = new Window(
                $this->_request->getHeader('X-Icinga-WindowId', Window::UNDEFINED)
            );
        }
        return $this->window;
    }

    protected function handlerBrowserWindows()
    {
        if ($this->isXhr()) {
            $id = $this->_request->getHeader('X-Icinga-WindowId', null);

            if ($id === Window::UNDEFINED) {
                $this->window = new Window($id);
                $this->_response->setHeader('X-Icinga-WindowId', Window::generateId());
            }
        }
    }

    protected function reloadCss()
    {
        $this->reloadCss = true;
        return $this;
    }

    /**
     * Respond with HTTP 405 if the current request's method is not one of the given methods
     *
     * @param   string $httpMethod                  Unlimited number of allowed HTTP methods
     *
     * @throws  \Zend_Controller_Action_Exception   If the request method is not one of the given methods
     */
    public function assertHttpMethod($httpMethod)
    {
        $httpMethods = array_flip(array_map('strtoupper', func_get_args()));
        if (! isset($httpMethods[$this->getRequest()->getMethod()])) {
            $this->getResponse()->setHeader('Allow', implode(', ', array_keys($httpMethods)));
            throw new \Zend_Controller_Action_Exception($this->translate('Method Not Allowed'), 405);
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
        return $this->Auth()->getRestrictions($name);
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

        return !$this->Auth()->isAuthenticated();
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
     * @param   string      $text       The string to translate
     * @param   string|null $context    Optional parameter for context based translation
     *
     * @return  string                  The translated string
     */
    public function translate($text, $context = null)
    {
        return Translator::translate($text, $this->view->translationDomain, $context);
    }

    /**
     * Translate a plural string
     *
     * @param string        $textSingular   The string in singular form to translate
     * @param string        $textPlural     The string in plural form to translate
     * @param string        $number         The number to get the plural or singular string
     * @param string|null   $context        Optional parameter for context based translation
     *
     * @return string                       The translated string
     */
    public function translatePlural($textSingular, $textPlural, $number, $context = null)
    {
        return Translator::translatePlural($textSingular, $textPlural, $number, $this->view->translationDomain, $context);
    }

    protected function ignoreXhrBody()
    {
        if ($this->isXhr()) {
            $this->getResponse()->setHeader('X-Icinga-Container', 'ignore');
        }
    }

    public function setAutorefreshInterval($interval)
    {
        if (! is_int($interval) || $interval < 1) {
            throw new ProgrammingError(
                'Setting autorefresh interval smaller than 1 second is not allowed'
            );
        }
        $this->autorefreshInterval = $interval;
        $this->_helper->layout()->autorefreshInterval = $interval;
        return $this;
    }

    public function disableAutoRefresh()
    {
        $this->autorefreshInterval = null;
        $this->_helper->layout()->autorefreshInterval = null;
        return $this;
    }

    /**
     * Redirect to login
     *
     * XHR will always redirect to __SELF__ if an URL to redirect to after successful login is set. __SELF__ instructs
     * JavaScript to redirect to the current window's URL if it's an auto-refresh request or to redirect to the URL
     * which required login if it's not an auto-refreshing one.
     *
     * XHR will respond with HTTP status code 403 Forbidden.
     *
     * @param   Url|string  $redirect   URL to redirect to after successful login
     */
    protected function redirectToLogin($redirect = null)
    {
        $login = Url::fromPath('authentication/login');
        if ($this->isXhr()) {
            if ($redirect !== null) {
                $login->setParam('redirect', '__SELF__');
            }

            $this->_response->setHttpResponseCode(403);
        } elseif ($redirect !== null) {
            if (! $redirect instanceof Url) {
                $redirect = Url::fromPath($redirect);
            }

            if (($relativeUrl = $redirect->getRelativeUrl())) {
                $login->setParam('redirect', $relativeUrl);
            }
        }

        $this->rerenderLayout()->redirectNow($login);
    }

    protected function rerenderLayout()
    {
        $this->rerenderLayout = true;
        $this->xhrLayout = 'layout';
        return $this;
    }

    public function isXhr()
    {
        return $this->getRequest()->isXmlHttpRequest();
    }

    protected function redirectXhr($url)
    {
        if (! $url instanceof Url) {
            $url = Url::fromPath($url);
        }

        if ($this->rerenderLayout) {
            $this->getResponse()->setHeader('X-Icinga-Rerender-Layout', 'yes');
        }
        if ($this->reloadCss) {
            $this->getResponse()->setHeader('X-Icinga-Reload-Css', 'now');
        }

        $this->shutdownSession();

        $this->getResponse()
            ->setHeader('X-Icinga-Redirect', rawurlencode($url->getAbsoluteUrl()))
            ->sendHeaders();

        exit;
    }

    protected function redirectHttp($url)
    {
        if (! $url instanceof Url) {
            $url = Url::fromPath($url);
        }
        $this->shutdownSession();
        $this->_helper->Redirector->gotoUrlAndExit($url->getRelativeUrl());
    }

    /**
    *  Redirect to a specific url, updating the browsers URL field
    *
    *  @param Url|string $url The target to redirect to
    **/
    public function redirectNow($url)
    {
        if ($this->isXhr()) {
            $this->redirectXhr($url);
        } else {
            $this->redirectHttp($url);
        }
    }

    /**
     * @see Zend_Controller_Action::preDispatch()
     */
    public function preDispatch()
    {
        $form = new AutoRefreshForm();
        $form->handleRequest();
        $this->_helper->layout()->autoRefreshForm = $form;
    }

    /**
     * Detect whether the current request requires changes in the layout and apply them before rendering
     *
     * @see Zend_Controller_Action::postDispatch()
     */
    public function postDispatch()
    {
        Benchmark::measure('Action::postDispatch()');

        $req = $this->getRequest();
        $layout = $this->_helper->layout();

        if ($user = $req->getUser()) {
            // Cast preference app.show_benchmark to bool because preferences loaded from a preferences storage are
            // always strings
            if ((bool) $user->getPreferences()->getValue('icingaweb', 'show_benchmark', false) === true) {
                if (!$this->_helper->viewRenderer->getNoRender()) {
                    $layout->benchmark = $this->renderBenchmark();
                }
            }

            if ((bool) $user->getPreferences()->getValue('icingaweb', 'auto_refresh', true) === false) {
                $this->disableAutoRefresh();
            }
        }

        if ($req->getParam('format') === 'pdf') {
            $this->shutdownSession();
            $this->sendAsPdf();
            exit;
        }

        if ($this->isXhr()) {
            $this->postDispatchXhr();
        }

        $this->shutdownSession();
    }

    protected function postDispatchXhr()
    {
        $layout = $this->_helper->layout();
        $layout->setLayout($this->xhrLayout);
        $resp = $this->getResponse();

        $notifications = Notification::getInstance();
        if ($notifications->hasMessages()) {
            $notificationList = array();
            foreach ($notifications->getMessages() as $m) {
                $notificationList[] = rawurlencode($m->type . ' ' . $m->message);
            }
            $resp->setHeader('X-Icinga-Notification', implode('&', $notificationList));
        }

        if ($this->reloadCss) {
            $resp->setHeader('X-Icinga-CssReload', 'now');
        }

        if ($this->view->title) {
            if (preg_match('~[\r\n]~', $this->view->title)) {
                // TODO: Innocent exception and error log for hack attempts
                throw new IcingaException('No way, guy');
            }
            $resp->setHeader(
                'X-Icinga-Title',
                rawurlencode($this->view->title . ' :: Icinga Web')
            );
        } else {
            $resp->setHeader('X-Icinga-Title', rawurlencode('Icinga Web'));
        }

        if ($this->rerenderLayout) {
            $this->getResponse()->setHeader('X-Icinga-Container', 'layout');
        }

        if ($this->autorefreshInterval !== null) {
            $resp->setHeader('X-Icinga-Refresh', $this->autorefreshInterval);
        }
    }

    protected function sendAsPdf()
    {
        $pdf = new Pdf();
        $pdf->renderControllerAction($this);
    }

    protected function shutdownSession()
    {
        $session = Session::getSession();
        if ($session->hasChanged()) {
            $session->write();
        }
    }

    /**
     * Render the benchmark
     *
     * @return string Benchmark HTML
     */
    protected function renderBenchmark()
    {
        $this->render();
        Benchmark::measure('Response ready');
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
