<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Controller;

use Icinga\Application\Config;
use Icinga\Application\Icinga;

/**
 * Base class for module action controllers
 */
class ModuleActionController extends ActionController
{
    private $config;

    private $configs = array();

    private $module;

    /**
     * Module name
     *
     * @var string
     */
    protected $moduleName;

    /**
     * Whether the module permission is required to access to module
     *
     * Note that module permissions do not have any effect if the controller does not require authentication.
     *
     * @var bool
     *
     * @see $requiresAuthentication For enabling/disabling whether the controller requires authentication.
     */
    protected $requiresModulePermission = true;

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Controller\ActionController For the method documentation.
     */
    protected function prepareInit()
    {
        $this->moduleName = $this->_request->getModuleName();
        $this->_helper->layout()->moduleName = $this->moduleName;
        $this->view->translationDomain = $this->moduleName;
        $this->moduleInit();
    }

    /**
     * Prepare module action controller initialization
     */
    protected function moduleInit()
    {
    }

    /**
     * Get whether the module permission is required to access to module
     *
     * @return bool
     */
    public function getRequiresModulePermission()
    {
        return $this->requiresModulePermission;
    }

    /**
     * Set whether the module permission is required to access to module
     *
     * @param   bool $required
     *
     * @return  $this
     */
    public function setRequiresModulePermission($required)
    {
        $this->requiresModulePermission = (bool) $required;
        return $this;
    }

    public function Config($file = null)
    {
        if ($file === null) {
            if ($this->config === null) {
                $this->config = Config::module($this->moduleName);
            }
            return $this->config;
        } else {
            if (! array_key_exists($file, $this->configs)) {
                $this->configs[$file] = Config::module($this->moduleName, $file);
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

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Controller\ActionController::postDispatchXhr() For the method documentation.
     */
    public function postDispatchXhr()
    {
        parent::postDispatchXhr();
        $this->getResponse()->setHeader('X-Icinga-Module', $this->moduleName);
    }
}
