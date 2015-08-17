<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Controller;

use Exception;
use LogicException;
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
     * Dispatch request to a controller and action
     *
     * @param Zend_Controller_Request_Abstract  $request
     * @param Zend_Controller_Response_Abstract $resposne
     */
    public function dispatch(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response)
    {
        $this->setResponse($response);
        $controllerName = $request->getControllerName();
        if (! $controllerName) {
            parent::dispatch($request, $response);
            return;
        }
        $controllerName = ucfirst($controllerName) . 'Controller';
        if ($this->_defaultModule === $this->_curModule) {
            $controllerClass = 'Icinga\\Controllers\\' . $controllerName;
        } else {
            $controllerClass = 'Icinga\\Module\\' . $this->_curModule . '\\Controllers\\' . $controllerName;
        }
        if (! class_exists($controllerClass)) {
            parent::dispatch($request, $response);
            return;
        }
        $controller = new $controllerClass($request, $response, $this->getParams());
        $actionName = $request->getActionName();
        if (! $actionName) {
            throw new LogicException('Action name not found');
        }
        $actionName = $actionName . 'Action';
        $request->setDispatched(true);
        // Buffer output by default
        $disableOb = $this->getParam('disableOutputBuffering');
        $obLevel = ob_get_level();
        if (empty($disableOb)) {
            ob_start();
        }
        try {
            $controller->dispatch($actionName);
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
