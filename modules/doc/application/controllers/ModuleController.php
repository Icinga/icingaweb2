<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Icinga;
use Icinga\Module\Doc\Controller as DocController;

class Doc_ModuleController extends DocController
{
    /**
     * Display module documentations index
     */
    public function indexAction()
    {
        $this->view->enabledModules = Icinga::app()->getModuleManager()->listEnabledModules();
    }

    /**
     * Display a module's documentation
     */
    public function viewAction()
    {
        $this->populateView($this->getParam('name'));
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
        $this->_helper->redirector->gotoSimpleAndExit('view', null, null, array('name' => $moduleName));
    }
}
