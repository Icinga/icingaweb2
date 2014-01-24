<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Icinga;
use Icinga\Web\Controller\ActionController;

class Doc_ModuleController extends ActionController
{
    /**
     * Display module documentations index
     */
    public function indexAction()
    {
    }

    /**
     * Display a module's documentation
     */
    public function moduleAction()
    {
    }

    /**
     * Provide run-time dispatching of module documentation
     *
     * @param   string    $methodName
     * @param   array     $args
     *
     * @return  mixed
     */
    public function __call($methodName, $args)
    {
        // TODO(el): Setup routing to retrieve module name as param and point route to moduleAction
        $moduleManager  = Icinga::app()->getModuleManager();
        $moduleName     = substr($methodName, 0, -6);  // Strip 'Action' suffix
        if (!$moduleManager->hasEnabled($moduleName)) {
            // TODO(el): Throw a not found exception once the code has been moved to the moduleAction (see TODO above)
            return parent::__call($methodName, $args);
        }
    }
}
// @codingStandardsIgnoreEnd
