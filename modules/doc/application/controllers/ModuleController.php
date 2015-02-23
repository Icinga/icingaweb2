<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use \Zend_Controller_Action_Exception;
use Icinga\Application\Icinga;
use Icinga\Module\Doc\DocController;
use Icinga\Module\Doc\Exception\DocException;

class Doc_ModuleController extends DocController
{
    /**
     * Get the path to a module documentation
     *
     * @param   string  $module                     The name of the module
     * @param   string  $default                    The default path
     * @param   bool    $suppressErrors             Whether to not throw an exception if the module documentation is not
     *                                              available
     *
     * @return  string|null                         Path to the documentation or null if the module documentation is not
     *                                              available and errors are suppressed
     *
     * @throws  Zend_Controller_Action_Exception    If the module documentation is not available and errors are not
     *                                              suppressed
     */
    protected function getPath($module, $default, $suppressErrors = false)
    {
        if (is_dir($default)) {
            return $default;
        }
        if (($path = $this->Config()->get('documentation', 'modules')) !== null) {
            $path = str_replace('{module}', $module, $path);
            if (is_dir($path)) {
                return $path;
            }
        }
        if ($suppressErrors) {
            return null;
        }
        throw new Zend_Controller_Action_Exception(
            sprintf($this->translate('Documentation for module \'%s\' is not available'), $module),
            404
        );
    }

    /**
     * List modules which are enabled and having the 'doc' directory
     */
    public function indexAction()
    {
        $moduleManager = Icinga::app()->getModuleManager();
        $modules = array();
        foreach ($moduleManager->listEnabledModules() as $module) {
            $path = $this->getPath($module, $moduleManager->getModuleDir($module, '/doc'), true);
            if ($path !== null) {
                $modules[] = $moduleManager->getModule($module);
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
        $module = $this->getParam('moduleName');
        $this->assertModuleEnabled($module);
        $this->view->moduleName = $module;
        try {
            $this->renderToc(
                $this->getPath($module, Icinga::app()->getModuleManager()->getModuleDir($module, '/doc')),
                $module,
                'doc/module/chapter',
                array('moduleName' => $module)
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
        $module = $this->getParam('moduleName');
        $this->assertModuleEnabled($module);
        $chapter = $this->getParam('chapter');
        if ($chapter === null) {
            throw new Zend_Controller_Action_Exception(
                sprintf($this->translate('Missing parameter %s'), 'chapter'),
                404
            );
        }
        $this->view->moduleName = $module;
        try {
            $this->renderChapter(
                $this->getPath($module, Icinga::app()->getModuleManager()->getModuleDir($module, '/doc')),
                $chapter,
                'doc/module/chapter',
                array('moduleName' => $module)
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
        $module = $this->getParam('moduleName');
        $this->assertModuleEnabled($module);
        $this->renderPdf(
            $this->getPath($module, Icinga::app()->getModuleManager()->getModuleDir($module, '/doc')),
            $module,
            'doc/module/chapter',
            array('moduleName' => $module)
        );
    }
}
