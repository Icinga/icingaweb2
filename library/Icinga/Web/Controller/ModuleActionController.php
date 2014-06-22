<?php

namespace Icinga\Web\Controller;

use Zend_Controller_Request_Abstract as Request;
use Zend_Controller_Response_Abstract as Response;

class ModuleActionController extends ActionController
{
    private $config;

    private $configs = array();

    protected $moduleName;

    public function __construct(
        Request $request,
        Response $response,
        array $invokeArgs = array()
    ) {
        parent::__construct($request, $response, $invokeArgs);
        $this->moduleName = $request->getModuleName();
        $this->view->translationDomain = $this->moduleName;
        $this->moduleInit();
    }

    public function Config($file = null)
    {
        $module = $this->getRequest()->getModuleName();

        $this->moduleName = $module;

        if ($tile === null) {
            if ($this->config === null) {
                $this->config = Config::module($module);
            }
            return $this->config;
        } else {
            if (! array_key_exists($file, $this->configs)) {
                $this->configs[$file] = Config::module($module, $file);
            }
            return $this->configs[$file];
        }
    }

    public function postDispatch()
    {
        $req = $this->getRequest();
        $resp = $this->getResponse();
        $layout = $this->_helper->layout();

        $isXhr = $req->isXmlHttpRequest();
        $layout->moduleName = $this->moduleName;
        if ($isXhr) {
            $resp->setHeader('X-Icinga-Module', $layout->moduleName);
        }

        parent::postDispatch();
    }

    protected function moduleInit()
    {
    }
}
