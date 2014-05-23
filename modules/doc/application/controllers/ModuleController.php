<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Icinga;
use Icinga\Module\Doc\Controller as DocController;

class Doc_ModuleController extends DocController
{
    /**
     * List available modules
     */
    public function indexAction()
    {
        $this->view->enabledModules = Icinga::app()->getModuleManager()->listEnabledModules();
    }

    /**
     * Provide run-time dispatching of module documentation
     *
     * @param   string  $methodName
     * @param   array   $args
     *
     * @return  mixed
     */
    public function __call($methodName, $args)
    {
        $moduleManager = Icinga::app()->getModuleManager();
        $moduleName = substr($methodName, 0, -6);  // Strip 'Action' suffix
        if (! $moduleManager->hasEnabled($moduleName)) {
            // TODO(el): Distinguish between not enabled and not installed
            return parent::__call($methodName, $args);
        }
        $this->renderDocAndToc($moduleName);
    }
}
