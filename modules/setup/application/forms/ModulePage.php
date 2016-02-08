<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Icinga\Application\Icinga;
use Icinga\Web\Form;

class ModulePage extends Form
{
    protected $modules;

    protected $modulePaths;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_modules');
        $this->setViewScript('form/setup-modules.phtml');

        $this->modulePaths = array();
        if (($appModulePath = realpath(Icinga::app()->getApplicationDir() . '/../modules')) !== false) {
            $this->modulePaths[] = $appModulePath;
        }
    }

    public function createElements(array $formData)
    {
        foreach ($this->getModules() as $module) {
            $this->addElement(
                'checkbox',
                $module->getName(),
                array(
                    'required'      => true,
                    'description'   => $module->getDescription(),
                    'label'         => ucfirst($module->getName()),
                    'value'         => $module->getName() === 'monitoring' ? 1 : 0,
                    'decorators'    => array('ViewHelper')
                )
            );
        }
    }

    protected function getModules()
    {
        if ($this->modules !== null) {
            return $this->modules;
        } else {
            $this->modules = array();
        }

        $moduleManager = Icinga::app()->getModuleManager();
        $moduleManager->detectInstalledModules($this->modulePaths);
        foreach ($moduleManager->listInstalledModules() as $moduleName) {
            if ($moduleName !== 'setup') {
                $this->modules[$moduleName] = $moduleManager->loadModule($moduleName)->getModule($moduleName);
            }
        }

        return $this->modules;
    }

    public function getCheckedModules()
    {
        $modules = $this->getModules();

        $checked = array();
        foreach ($this->getElements() as $name => $element) {
            if (array_key_exists($name, $modules) && $element->isChecked()) {
                $checked[$name] = $modules[$name];
            }
        }

        return $checked;
    }

    public function getModuleWizards()
    {
        $checked = $this->getCheckedModules();

        $wizards = array();
        foreach ($checked as $name => $module) {
            if ($module->providesSetupWizard()) {
                $wizards[$name] = $module->getSetupWizard();
            }
        }

        return $wizards;
    }
}
