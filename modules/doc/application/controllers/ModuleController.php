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
                sprintf($this->translate('Missing parameter \'%s\''), 'moduleName'),
                404
            );
        }
        $moduleManager = Icinga::app()->getModuleManager();
        if (! $moduleManager->hasInstalled($moduleName)) {
            throw new Zend_Controller_Action_Exception(
                sprintf($this->translate('Module \'%s\' is not installed'), $moduleName),
                404
            );
        }
        if (! $moduleManager->hasEnabled($moduleName)) {
            throw new Zend_Controller_Action_Exception(
                sprintf($this->translate('Module \'%s\' is not enabled'), $moduleName),
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
        $this->view->moduleName = $moduleName;
        $moduleManager = Icinga::app()->getModuleManager();
        try {
            return $this->renderToc(
                $moduleManager->getModuleDir($moduleName, '/doc'),
                $moduleName,
                'doc/module/chapter',
                array('moduleName' => $moduleName)
            );
        } catch (DocException $e) {
            throw new Zend_Controller_Action_Exception($e->getMessage(), 404);
        }
    }

    /**
     * View a chapter of a module's documentation
     *
     * @throws  Zend_Controller_Action_Exception    If the required parameter 'chapterId' is missing or if an error in
     *                                              the documentation module's library occurs
     * @see     assertModuleEnabled()
     */
    public function chapterAction()
    {
        $moduleName = $this->getParam('moduleName');
        $this->assertModuleEnabled($moduleName);
        $chapterId = $this->getParam('chapterId');
        if ($chapterId === null) {
            throw new Zend_Controller_Action_Exception(
                $this->translate('Missing parameter \'chapterId\''),
                404
            );
        }
        $this->view->moduleName = $moduleName;
        $moduleManager = Icinga::app()->getModuleManager();
        try {
            return $this->renderChapter(
                $moduleManager->getModuleDir($moduleName, '/doc'),
                $chapterId,
                $this->_helper->url->url(array('moduleName' => $moduleName), 'doc/module/toc'),
                'doc/module/chapter',
                array('moduleName' => $moduleName)
            );
        } catch (DocException $e) {
            throw new Zend_Controller_Action_Exception($e->getMessage(), 404);
        }
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
        return $this->renderPdf(
            $moduleManager->getModuleDir($moduleName, '/doc'),
            $moduleName,
            'doc/module/chapter',
            array('moduleName' => $moduleName)
        );
    }
}
