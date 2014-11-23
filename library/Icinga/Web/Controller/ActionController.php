<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Controller;

use Exception;
use Icinga\Authentication\Manager as AuthManager;
use Icinga\Application\Benchmark;
use Icinga\Application\Config;
use Icinga\Exception\IcingaException;
use Icinga\Util\Translator;
use Icinga\Web\Widget\Tabs;
use Icinga\Web\Window;
use Icinga\Web\Url;
use Icinga\Web\Notification;
use Icinga\File\Pdf;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Session;
use Icinga\Web\UrlParams;
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

    private $auth;

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

    public function Config($file = null)
    {
        if ($file === null) {
            return Config::app();
        } else {
            return Config::app($file);
        }
    }

    public function Auth()
    {
        if ($this->auth === null) {
            $this->auth = AuthManager::getInstance();
        }
        return $this->auth;
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
     * Whether the user currently authenticated has the given permission
     *
     * @param  string  $name Permission name
     * @return bool
     */
    public function hasPermission($name)
    {
        return $this->Auth()->hasPermission($name);
    }

    /**
     * Throws an exception if user lacks the given permission
     *
     * @param  string  $name Permission name
     * @throws Exception
     */
    public function assertPermission($name)
    {
        if (! $this->Auth()->hasPermission($name)) {
            // TODO: Shall this be an Auth Exception? Or a 404?
            throw new IcingaException(
                'Auth error, no permission for "%s"',
                $name
            );
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
     * Redirect to the login path
     *
     * @param   string      $afterLogin   The action to call when the login was successful. Defaults to '/index/welcome'
     *
     * @throws  \Exception
     */
    protected function redirectToLogin($afterLogin = null)
    {
        $redir = null;
        if ($afterLogin !== null) {
            if (! $afterLogin instanceof Url) {
                $afterLogin = Url::fromPath($afterLogin);
            }
            if ($this->isXhr()) {
                $redir = '__SELF__';
            } else {
                // TODO: Ignore /?
                $redir = $afterLogin->getRelativeUrl();
            }
        }

        $url = Url::fromPath('authentication/login');

        if ($redir) {
            $url->setParam('redirect', $redir);
        }

        $this->rerenderLayout()->redirectNow($url);
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
        }

        if ($req->getParam('format') === 'pdf') {
            $layout->setLayout('pdf');
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
