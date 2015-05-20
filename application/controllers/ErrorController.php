<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Exception\MissingParameterException;
use Icinga\Security\SecurityException;
use Icinga\Web\Controller\ActionController;

/**
 * Application wide controller for displaying exceptions
 */
class ErrorController extends ActionController
{
    protected $requiresAuthentication = false;

    /**
     * Display exception
     */
    public function errorAction()
    {
        $error      = $this->_getParam('error_handler');
        $exception  = $error->exception;

        Logger::error($exception);
        Logger::error('Stacktrace: %s', $exception->getTraceAsString());

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
                if ($modules->hasInstalled($path) && ! $modules->hasEnabled($path)) {
                    $this->view->message .= ' ' . sprintf(
                        $this->translate('Enabling the "%s" module might help!'),
                        $path
                    );
                }

                break;
            default:
                switch (true) {
                    case $exception instanceof SecurityException:
                        $this->getResponse()->setHttpResponseCode(403);
                        break;
                    case $exception instanceof MissingParameterException:
                        $this->getResponse()->setHttpResponseCode(400);
                        $this->getResponse()->setHeader(
                            'X-Status-Reason',
                            'Missing parameter ' . $exception->getParameter()
                        );
                        break;
                    default:
                        $this->getResponse()->setHttpResponseCode(500);
                        break;
                }
                $title = preg_replace('/\r?\n.*$/s', '', $exception->getMessage());
                $this->view->title = 'Server error: ' . $title;
                $this->view->message = $exception->getMessage();
                if ($this->getInvokeArg('displayExceptions')) {
                    $this->view->stackTrace = $exception->getTraceAsString();
                }
                break;
        }
        $this->view->request = $error->request;
    }
}
