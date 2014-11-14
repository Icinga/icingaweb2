<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Setup\Forms;

use InvalidArgumentException;
use Icinga\Application\Icinga;
use Icinga\Web\Form;
use Icinga\Web\Session;
use Icinga\Web\Request;

class ModulePage extends Form
{
    protected $session;

    protected $wizards;

    protected $modules;

    protected $pageData;

    protected $modulePaths;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_modules');
        $this->setViewScript('form/setup-modules.phtml');
        $this->session = Session::getSession()->getNamespace(get_class($this));

        $this->modulePaths = array();
        if (($appModulePath = realpath(Icinga::app()->getApplicationDir() . '/../modules')) !== false) {
            $this->modulePaths[] = $appModulePath;
        }
    }

    public function setPageData(array $pageData)
    {
        $this->pageData = $pageData;
        return $this;
    }

    public function handleRequest(Request $request = null)
    {
        if ($this->wasSent($this->getRequestData($request))) {
            if (($newModule = $request->getPost('module')) !== null) {
                $this->setCurrentModule($newModule);
                $this->getResponse()->redirectAndExit($this->getRedirectUrl());
            } else {
                // The user submitted this form but with the parent wizard's navigation
                // buttons so it's now up to the parent wizard to handle the request..
            }
        } else {
            $wizard = $this->getCurrentWizard();
            $wizardPage = $wizard->getCurrentPage();

            $wizard->handleRequest($request);
            if ($wizard->isFinished() && $wizardPage->wasSent($wizardPage->getRequestData($request))) {
                $wizards = $this->getWizards();

                $newModule = null;
                foreach ($wizards as $moduleName => $moduleWizard) {
                    if (false === $moduleWizard->isFinished()) {
                        $newModule = $moduleName;
                    }
                }

                if ($newModule === null) {
                    // In case all module wizards were completed just pick the first one again
                    reset($wizards);
                    $newModule = key($wizards);
                }

                $this->setCurrentModule($newModule);
            }
        }
    }

    public function clearSession()
    {
        $this->session->clear();
        foreach ($this->getWizards() as $wizard) {
            $wizard->clearSession();
        }
    }

    public function setCurrentModule($moduleName)
    {
        if (false === array_key_exists($moduleName, $this->getWizards())) {
            throw new InvalidArgumentException(sprintf('Module "%s" does not provide a setup wizard', $moduleName));
        }

        $this->session->currentModule = $moduleName;
    }

    public function getCurrentModule()
    {
        $moduleName = $this->session->get('currentModule');
        if ($moduleName === null) {
            $moduleName = key($this->getWizards());
            $this->setCurrentModule($moduleName);
        }

        return $moduleName;
    }

    public function getCurrentWizard()
    {
        $wizards = $this->getWizards();
        return $wizards[$this->getCurrentModule()];
    }

    public function getModules()
    {
        if ($this->modules !== null) {
            return $this->modules;
        } else {
            $this->modules = array();
        }

        $moduleManager = Icinga::app()->getModuleManager();
        $moduleManager->detectInstalledModules($this->modulePaths);
        foreach ($moduleManager->listInstalledModules() as $moduleName) {
            $this->modules[] = $moduleManager->loadModule($moduleName)->getModule($moduleName);
        }

        return $this->modules;
    }

    public function getWizards()
    {
        if ($this->wizards !== null) {
            return $this->wizards;
        } else {
            $this->wizards = array();
        }

        foreach ($this->getModules() as $module) {
            if ($module->providesSetupWizard()) {
                $this->wizards[$module->getName()] = $module->getSetupWizard();
            }
        }

        $this->mergePageData($this->wizards);
        return $this->wizards;
    }

    protected function mergePageData(array $wizards)
    {
        foreach ($wizards as $wizard) {
            $wizardPageData = & $wizard->getPageData();
            foreach ($this->pageData as $pageName => $pageData) {
                $wizardPageData[$pageName] = $pageData;
            }
        }
    }
}
