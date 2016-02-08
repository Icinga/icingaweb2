<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Controller;

use Exception;
use Icinga\Util\StringHelper;
use Zend_Controller_Action;
use Zend_Controller_Action_Interface;
use Zend_Controller_Dispatcher_Exception;
use Zend_Controller_Dispatcher_Standard;
use Zend_Controller_Request_Abstract;
use Zend_Controller_Response_Abstract;

/**
 * Dispatcher supporting Zend-style and namespaced controllers
 *
 * Does not support a namespaced default controller in combination w/ the Zend parameter useDefaultControllerAlways.
 */
class Dispatcher extends Zend_Controller_Dispatcher_Standard
{
    /**
     * Controller namespace
     *
     * @var string
     */
    const CONTROLLER_NAMESPACE = 'Controllers';

    /**
     * Dispatch request to a controller and action
     *
     * @param   Zend_Controller_Request_Abstract  $request
     * @param   Zend_Controller_Response_Abstract $response
     *
     * @throws  Zend_Controller_Dispatcher_Exception    If the controller is not an instance of
     *                                                  Zend_Controller_Action_Interface
     * @throws  Exception                               If dispatching the request fails
     */
    public function dispatch(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response)
    {
        $this->setResponse($response);
        $controllerName = $request->getControllerName();
        if (! $controllerName) {
            parent::dispatch($request, $response);
            return;
        }
        $controllerName = StringHelper::cname($controllerName, '-') . 'Controller';
        $moduleName = $request->getModuleName();
        if ($moduleName === null || $moduleName === $this->_defaultModule) {
            $controllerClass = 'Icinga\\' . self::CONTROLLER_NAMESPACE . '\\' . $controllerName;
        } else {
            $controllerClass = 'Icinga\\Module\\' . ucfirst($moduleName) . '\\' . self::CONTROLLER_NAMESPACE . '\\'
                . $controllerName;
        }
        if (! class_exists($controllerClass)) {
            parent::dispatch($request, $response);
            return;
        }
        $controller = new $controllerClass($request, $response, $this->getParams());
        if (! $controller instanceof Zend_Controller_Action
            && ! $controller instanceof Zend_Controller_Action_Interface
        ) {
            throw new Zend_Controller_Dispatcher_Exception(
                'Controller "' . $controllerClass . '" is not an instance of Zend_Controller_Action_Interface'
            );
        }
        $action = $this->getActionMethod($request);
        $request->setDispatched(true);
        // Buffer output by default
        $disableOb = $this->getParam('disableOutputBuffering');
        $obLevel = ob_get_level();
        if (empty($disableOb)) {
            ob_start();
        }
        try {
            $controller->dispatch($action);
        } catch (Exception $e) {
            // Clean output buffer on error
            $curObLevel = ob_get_level();
            if ($curObLevel > $obLevel) {
                do {
                    ob_get_clean();
                    $curObLevel = ob_get_level();
                } while ($curObLevel > $obLevel);
            }
            throw $e;
        }
        if (empty($disableOb)) {
            $content = ob_get_clean();
            $response->appendBody($content);
        }
    }
}
