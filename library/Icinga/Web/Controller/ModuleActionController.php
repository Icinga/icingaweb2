<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Controller;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Zend_Controller_Request_Abstract as Request;
use Zend_Controller_Response_Abstract as Response;

class ModuleActionController extends ActionController
{
    private $config;

    private $configs = array();

    private $module;

    protected $moduleName;

    public function __construct(
        Request $request,
        Response $response,
        array $invokeArgs = array()
    ) {
        parent::__construct($request, $response, $invokeArgs);
        $this->moduleName = $request->getModuleName();
        $this->_helper->layout()->moduleName = $this->moduleName;
        $this->view->translationDomain = $this->moduleName;
        $this->moduleInit();
    }

    public function Config($file = null)
    {
        $module = $this->getRequest()->getModuleName();

        $this->moduleName = $module;

        if ($file === null) {
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

    public function Module()
    {
        if ($this->module === null) {
            $this->module = Icinga::app()->getModuleManager()->getModule($this->moduleName);
        }
        return $this->module;
    }

    public function postDispatch()
    {
        $req = $this->getRequest();
        $resp = $this->getResponse();

        if ($this->isXhr()) {
            $resp->setHeader('X-Icinga-Module', $this->moduleName);
        }

        parent::postDispatch();
    }

    protected function moduleInit()
    {
    }
}
