<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Controller;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Manager;
use Icinga\Application\Modules\Module;

/**
 * Base class for module action controllers
 */
class ModuleActionController extends ActionController
{
    protected $config;

    protected $configs = array();

    protected $module;

    /**
     * (non-PHPDoc)
     * @see \Icinga\Web\Controller\ActionController For the method documentation.
     */
    protected function prepareInit()
    {
        $this->moduleInit();
        if (($this->Auth()->isAuthenticated() || $this->requiresLogin())
            && $this->getFrontController()->getDefaultModule() !== $this->getModuleName()) {
            $this->assertPermission(Manager::MODULE_PERMISSION_NS . $this->getModuleName());
        }
    }

    /**
     * Prepare module action controller initialization
     */
    protected function moduleInit()
    {
    }

    public function Config($file = null)
    {
        if ($file === null) {
            if ($this->config === null) {
                $this->config = Config::module($this->getModuleName());
            }
            return $this->config;
        } else {
            if (! array_key_exists($file, $this->configs)) {
                $this->configs[$file] = Config::module($this->getModuleName(), $file);
            }
            return $this->configs[$file];
        }
    }

    /**
     * Return this controller's module
     *
     * @return  Module
     */
    public function Module()
    {
        if ($this->module === null) {
            $this->module = Icinga::app()->getModuleManager()->getModule($this->getModuleName());
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
        $this->getResponse()->setHeader('X-Icinga-Module', $this->getModuleName(), true);
    }
}
