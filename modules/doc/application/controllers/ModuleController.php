<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \Zend_Controller_Action_Exception;
use Icinga\Application\Icinga;
use Icinga\Module\Doc\DocController;

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
     * Assert that the given module is enabled
     *
     * @param   $moduleName
     *
     * @throws  Zend_Controller_Action_Exception
     */
    protected function assertModuleEnabled($moduleName)
    {
        if ($moduleName === null) {
            throw new Zend_Controller_Action_Exception('Missing parameter "moduleName"', 404);
        }
        $moduleManager = Icinga::app()->getModuleManager();
        if (! $moduleManager->hasInstalled($moduleName)) {
            throw new Zend_Controller_Action_Exception('Module ' . $moduleName . ' is not installed', 404);
        }
        if (! $moduleManager->hasEnabled($moduleName)) {
            throw new Zend_Controller_Action_Exception('Module ' . $moduleName. ' is not enabled', 404);
        }
    }

    /**
     * View toc of a module's documentation
     */
    public function tocAction()
    {
        $moduleName = $this->getParam('moduleName');
        $this->assertModuleEnabled($moduleName);
        $moduleManager = Icinga::app()->getModuleManager();
        $this->populateToc($moduleManager->getModuleDir($moduleName, '/doc'), $moduleName);
        $this->view->moduleName = $moduleName;
    }

    /**
     * View a chapter of a module's documentation
     *
     * @throws Zend_Controller_Action_Exception
     */
    public function chapterAction()
    {
        $moduleName = $this->getParam('moduleName');
        $this->assertModuleEnabled($moduleName);
        $chapterName = $this->getParam('chapterName');
        if ($chapterName === null) {
            throw new Zend_Controller_Action_Exception('Missing parameter "chapterName"', 404);
        }
        $moduleManager = Icinga::app()->getModuleManager();
        $this->populateChapter($chapterName, $moduleManager->getModuleDir($moduleName, '/doc'));
        $this->view->moduleName = $moduleName;
    }
}
