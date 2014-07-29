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
     * @throws  Zend_Controller_Action_Exception    If the required parameter 'moduleName' is empty or either if the
     *                                              given module is neither installed nor enabled
     */
    protected function assertModuleEnabled($moduleName)
    {
        if (empty($moduleName)) {
            throw new Zend_Controller_Action_Exception(
                $this->translate('Missing parameter \'moduleName\''),
                404
            );
        }
        $moduleManager = Icinga::app()->getModuleManager();
        if (! $moduleManager->hasInstalled($moduleName)) {
            throw new Zend_Controller_Action_Exception(
                $this->translate('Module') . ' \'' . $moduleName . '\' ' . $this->translate('is not installed'),
                404
            );
        }
        if (! $moduleManager->hasEnabled($moduleName)) {
            throw new Zend_Controller_Action_Exception(
                $this->translate('Module') . ' \'' . $moduleName . '\' ' . $this->translate('is not enabled'),
                404
            );
        }
    }

    /**
     * View the toc of a module's documentation
     *
     * @see assertModuleEnabled()
     */
    public function tocAction()
    {
        $moduleName = $this->getParam('moduleName');
        $this->assertModuleEnabled($moduleName);
        $moduleManager = Icinga::app()->getModuleManager();
        try {
            $this->renderToc(
                $moduleManager->getModuleDir($moduleName, '/doc'),
                $moduleName,
                'doc/module/chapter',
                array('moduleName' => $moduleName)
            );
        } catch (DocException $e) {
            throw new Zend_Controller_Action_Exception($e->getMessage(), 404);
        }
        $this->view->moduleName = $moduleName;
    }

    /**
     * View a chapter of a module's documentation
     *
     * @throws  Zend_Controller_Action_Exception    If the required parameter 'chapterTitle' is missing or if an error in
     *                                              the documentation module's library occurs
     * @see     assertModuleEnabled()
     */
    public function chapterAction()
    {
        $moduleName = $this->getParam('moduleName');
        $this->assertModuleEnabled($moduleName);
        $chapterTitle = $this->getParam('chapterTitle');
        if ($chapterTitle === null) {
            throw new Zend_Controller_Action_Exception(
                $this->translate('Missing parameter \'chapterTitle\''),
                404
            );
        }
        $moduleManager = Icinga::app()->getModuleManager();
        try {
            $this->renderChapter(
                $moduleManager->getModuleDir($moduleName, '/doc'),
                $chapterTitle,
                'doc/module/chapter',
                array('moduleName' => $moduleName)
            );
        } catch (DocException $e) {
            throw new Zend_Controller_Action_Exception($e->getMessage(), 404);
        }
        $this->view->moduleName = $moduleName;
    }

    /**
     * View a module's documentation as PDF
     *
     * @see assertModuleEnabled()
     */
    public function pdfAction()
    {
        $moduleName = $this->getParam('moduleName');
        $this->assertModuleEnabled($moduleName);
        $moduleManager = Icinga::app()->getModuleManager();
        $this->renderPdf(
            $moduleManager->getModuleDir($moduleName, '/doc'),
            $moduleName,
            'doc/module/chapter',
            array('moduleName' => $moduleName)
        );
    }
}
