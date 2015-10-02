<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc\Controllers;

use Icinga\Application\Icinga;
use Icinga\Module\Doc\DocController;
use Icinga\Module\Doc\Exception\DocException;

class ModuleController extends DocController
{
    /**
     * Get the path to a module documentation
     *
     * @param   string  $module         The name of the module
     * @param   string  $default        The default path
     * @param   bool    $suppressErrors Whether to not throw an exception if the module documentation is not available
     *
     * @return  string|null             Path to the documentation or null if the module documentation is not available
     *                                  and errors are suppressed
     *
     * @throws  \Icinga\Exception\Http\HttpNotFoundException    If the module documentation is not available and errors
     *                                                          are not suppressed
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
        $this->httpNotFound($this->translate('Documentation for module \'%s\' is not available'), $module);
    }

    /**
     * List modules which are enabled and having the 'doc' directory
     */
    public function indexAction()
    {
        $moduleManager = Icinga::app()->getModuleManager();
        $modules = array();
        foreach ($moduleManager->listInstalledModules() as $module) {
            $path = $this->getPath($module, $moduleManager->getModuleDir($module, '/doc'), true);
            if ($path !== null) {
                $modules[] = $moduleManager->getModule($module, false);
            }
        }
        $this->view->modules = $modules;
    }

    /**
     * Assert that the given module is installed
     *
     * @param   string $moduleName
     *
     * @throws  \Icinga\Exception\Http\HttpNotFoundException If the given module is not installed
     */
    protected function assertModuleInstalled($moduleName)
    {
        $moduleManager = Icinga::app()->getModuleManager();
        if (! $moduleManager->hasInstalled($moduleName)) {
            $this->httpNotFound($this->translate('Module \'%s\' is not installed'), $moduleName);
        }
    }

    /**
     * View the toc of a module's documentation
     *
     * @throws  \Icinga\Exception\MissingParameterException     If the required parameter 'moduleName' is empty
     * @throws  \Icinga\Exception\Http\HttpNotFoundException    If the given module is not installed
     * @see     assertModuleInstalled()
     */
    public function tocAction()
    {
        $module = $this->params->getRequired('moduleName');
        $this->assertModuleInstalled($module);
        $this->view->moduleName = $module;
        try {
            $this->renderToc(
                $this->getPath($module, Icinga::app()->getModuleManager()->getModuleDir($module, '/doc')),
                $module,
                'doc/module/chapter',
                array('moduleName' => $module)
            );
        } catch (DocException $e) {
            $this->httpNotFound($e->getMessage());
        }
    }

    /**
     * View a chapter of a module's documentation
     *
     * @throws  \Icinga\Exception\MissingParameterException     If one of the required parameters 'moduleName' and
     *                                                          'chapter' is empty
     * @throws  \Icinga\Exception\Http\HttpNotFoundException    If the given module is not installed
     * @see     assertModuleInstalled()
     */
    public function chapterAction()
    {
        $module = $this->params->getRequired('moduleName');
        $this->assertModuleInstalled($module);
        $chapter = $this->params->getRequired('chapter');
        $this->view->moduleName = $module;
        try {
            $this->renderChapter(
                $this->getPath($module, Icinga::app()->getModuleManager()->getModuleDir($module, '/doc')),
                $chapter,
                'doc/module/chapter',
                array('moduleName' => $module)
            );
        } catch (DocException $e) {
            $this->httpNotFound($e->getMessage());
        }
    }

    /**
     * View a module's documentation as PDF
     *
     * @throws  \Icinga\Exception\MissingParameterException     If the required parameter 'moduleName' is empty
     * @throws  \Icinga\Exception\Http\HttpNotFoundException    If the given module is not installed
     * @see     assertModuleInstalled()
     */
    public function pdfAction()
    {
        $module = $this->params->getRequired('moduleName');
        $this->assertModuleInstalled($module);
        $this->renderPdf(
            $this->getPath($module, Icinga::app()->getModuleManager()->getModuleDir($module, '/doc')),
            $module,
            'doc/module/chapter',
            array('moduleName' => $module)
        );
    }
}
