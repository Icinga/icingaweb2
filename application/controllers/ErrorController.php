<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

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

        switch ($error->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                $modules = Icinga::app()->getModuleManager();
                $path = ltrim($this->_request->get('PATH_INFO'), '/');
                $path = preg_split('~/~', $path);
                $path = array_shift($path);
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = $this->translate('Page not found.');
                if ($isAuthenticated) {
                    if ($modules->hasInstalled($path) && ! $modules->hasEnabled($path)) {
                        $this->view->message .= ' ' . sprintf(
                            $this->translate('Enabling the "%s" module might help!'),
                            $path
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
                        $this->getResponse()->setHttpResponseCode(500);
                        Logger::error("%s\n%s", $exception, $exception->getTraceAsString());
                        break;
                }
                $this->view->message = $exception->getMessage();
                if ($this->getInvokeArg('displayExceptions')) {
                    $this->view->stackTrace = $exception->getTraceAsString();
                }

                break;
        }

        if ($this->getRequest()->isApiRequest()) {
            $this->getResponse()->json()
                ->setErrorMessage($this->view->message)
                ->sendResponse();
        }

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
