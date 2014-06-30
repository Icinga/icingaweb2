<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \Zend_Controller_Action_Exception;
use Icinga\Application\Icinga;
use Icinga\Module\Doc\DocController;
use Icinga\Module\Doc\Exception\DocException;

class Doc_ModuleController extends DocController
{
    /**
     * List modules which are enabled and having the 'doc' directory
     */
    public function indexAction()
    {
        $moduleManager = Icinga::app()->getModuleManager();
        $modules = array();
        foreach (Icinga::app()->getModuleManager()->listEnabledModules() as $enabledModule) {
            $docDir = $moduleManager->getModuleDir($enabledModule, '/doc');
            if (is_dir($docDir)) {
                $modules[] = $enabledModule;
            }
        }
        $this->view->modules = $modules;
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
        try {
            $this->populateToc($moduleManager->getModuleDir($moduleName, '/doc'), $moduleName);
        } catch (DocException $e) {
            throw new Zend_Controller_Action_Exception($e->getMessage(), 404);
        }
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
        try {
            $this->populateChapter($chapterName, $moduleManager->getModuleDir($moduleName, '/doc'));
        } catch (DocException $e) {
            throw new Zend_Controller_Action_Exception($e->getMessage(), 404);
        }
        $this->view->moduleName = $moduleName;
    }
}
