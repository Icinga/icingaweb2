<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Hook\MigrationHook;
use Icinga\Application\MigrationManager;
use Icinga\Exception\IcingaException;
use Zend_Controller_Plugin_ErrorHandler;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Exception\Http\HttpExceptionInterface;
use Icinga\Exception\MissingParameterException;
use Icinga\Security\SecurityException;
use Icinga\Web\Controller\ActionController;
use Icinga\Web\Url;

/**
 * Application wide controller for displaying exceptions
 */
class ErrorController extends ActionController
{
    /**
     * Regular expression to match exceptions resulting from missing functions/classes
     */
    const MISSING_DEP_ERROR =
        "/Uncaught Error:.*(?:undefined function (\S+)|Class ['\"]([^']+)['\"] not found).* in ([^:]+)/";

    /**
     * {@inheritdoc}
     */
    protected $requiresAuthentication = false;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->rerenderLayout = $this->params->has('renderLayout');
    }

    /**
     * Display exception
     */
    public function errorAction()
    {
        $error      = $this->_getParam('error_handler');
        $exception  = $error->exception;
        /** @var \Exception $exception */

        if (! ($isAuthenticated = $this->Auth()->isAuthenticated())) {
            $this->innerLayout = 'guest-error';
        }

        $modules = Icinga::app()->getModuleManager();
        $sourcePath = ltrim($this->_request->get('PATH_INFO'), '/');
        $pathParts = preg_split('~/~', $sourcePath);
        $moduleName = array_shift($pathParts);

        $module = null;
        switch ($error->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->messages = array($this->translate('Page not found.'));
                if ($isAuthenticated) {
                    if ($modules->hasInstalled($moduleName) && ! $modules->hasEnabled($moduleName)) {
                        $this->view->messages[0] .= ' ' . sprintf(
                            $this->translate('Enabling the "%s" module might help!'),
                            $moduleName
                        );
                    }
                }

                break;
            default:
                switch (true) {
                    case $exception instanceof HttpExceptionInterface:
                        $this->getResponse()->setHttpResponseCode($exception->getStatusCode());
                        foreach ($exception->getHeaders() as $name => $value) {
                            $this->getResponse()->setHeader($name, $value, true);
                        }
                        break;
                    case $exception instanceof MissingParameterException:
                        $this->getResponse()->setHttpResponseCode(400);
                        $this->getResponse()->setHeader(
                            'X-Status-Reason',
                            'Missing parameter ' . $exception->getParameter()
                        );
                        break;
                    case $exception instanceof SecurityException:
                        $this->getResponse()->setHttpResponseCode(403);
                        break;
                    default:
                        $mm = MigrationManager::instance();
                        $action = $this->getRequest()->getActionName();
                        $controller = $this->getRequest()->getControllerName();
                        if ($action !== 'hint' && $controller !== 'migrations' && $mm->hasMigrations($moduleName)) {
                            // The view renderer from IPL web doesn't render the HTML content set in the respective
                            // controller if the error_handler request param is set, as it doesn't support error
                            // rendering. Since this error handler isn't caused by the migrations controller, we can
                            // safely unset this.
                            $this->setParam('error_handler', null);
                            $this->forward('hint', 'migrations', 'default', [
                                MigrationHook::MIGRATION_PARAM => $moduleName
                            ]);

                            return;
                        }

                        $this->getResponse()->setHttpResponseCode(500);
                        $module = $modules->hasLoaded($moduleName) ? $modules->getModule($moduleName) : null;
                        Logger::error("%s\n%s", $exception, IcingaException::getConfidentialTraceAsString($exception));
                        break;
                }

                // Try to narrow down why the request has failed
                if (preg_match(self::MISSING_DEP_ERROR, $exception->getMessage(), $match)) {
                    $sourcePath = $match[3];
                    foreach ($modules->listLoadedModules() as $name) {
                        $candidate = $modules->getModule($name);
                        $modulePath = $candidate->getBaseDir();
                        if (substr($sourcePath, 0, strlen($modulePath)) === $modulePath) {
                            $module = $candidate;
                            break;
                        }
                    }

                    if (preg_match('/^(?:Icinga\\\Module\\\(\w+)|(\w+)\\\(\w+))/', $match[1] ?: $match[2], $natch)) {
                        $this->view->requiredModule = isset($natch[1]) ? strtolower($natch[1]) : null;
                        $this->view->requiredVendor = isset($natch[2]) ? $natch[2] : null;
                        $this->view->requiredProject = isset($natch[3]) ? $natch[3] : null;
                    }
                }

                $this->view->messages = array();

                if ($this->getInvokeArg('displayExceptions')) {
                    $this->view->stackTraces = array();

                    do {
                        $this->view->messages[] = $exception->getMessage();
                        $this->view->stackTraces[] = IcingaException::getConfidentialTraceAsString($exception);
                        $exception = $exception->getPrevious();
                    } while ($exception !== null);
                } else {
                    do {
                        $this->view->messages[] = $exception->getMessage();
                        $exception = $exception->getPrevious();
                    } while ($exception !== null);
                }

                break;
        }

        if ($this->getRequest()->isApiRequest()) {
            $this->getResponse()->json()
                ->setErrorMessage($this->view->messages[0])
                ->sendResponse();
        }

        $this->view->module = $module;
        $this->view->request = $error->request;
        if (! $isAuthenticated) {
            $this->view->hideControls = true;
        } else {
            $this->view->hideControls = false;
            $this->getTabs()->add('error', array(
                'active'    => true,
                'label'     => $this->translate('Error'),
                'url'       => Url::fromRequest()
            ));
        }
    }
}
