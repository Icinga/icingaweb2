<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Setup\Forms;

use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;
use Icinga\Web\Form;

class ModulePage extends Form
{
    protected $modules;

    protected $modulePaths;

    protected $foundIcingaDB = false;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_modules');
        $this->setViewScript('form/setup-modules.phtml');

        $this->modulePaths = [];
        if (($appModulePath = realpath(Icinga::app()->getApplicationDir() . '/../modules')) !== false) {
            $this->modulePaths[] = $appModulePath;
        }
    }

    public function createElements(array $formData)
    {
        foreach ($this->getModules() as $module) {
            $checked = false;
            if ($module->getName() === 'monitoring') {
                $checked = ! $this->foundIcingaDB;
            } elseif ($this->foundIcingaDB && $module->getName() === 'icingadb') {
                $checked = true;
            }

            $this->addElement(
                'checkbox',
                $module->getName(),
                [
                    'description'   => $module->getDescription(),
                    'label'         => ucfirst($module->getName()),
                    'value'         => (int) $checked,
                    'decorators'    => ['ViewHelper']
                ]
            );
        }
    }

    /**
     * @return Module[]
     */
    protected function getModules()
    {
        if ($this->modules !== null) {
            return $this->modules;
        } else {
            $this->modules = [];
        }

        $moduleManager = Icinga::app()->getModuleManager();
        $moduleManager->detectInstalledModules($this->modulePaths);
        foreach ($moduleManager->listInstalledModules() as $moduleName) {
            if ($moduleName !== 'setup') {
                $this->modules[$moduleName] = $moduleManager->loadModule($moduleName)->getModule($moduleName);
            }

            if ($moduleName === 'icingadb') {
                $this->foundIcingaDB = true;
            }
        }

        return $this->modules;
    }

    public function getCheckedModules()
    {
        $modules = $this->getModules();

        $checked = [];
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

        $wizards = [];
        foreach ($checked as $name => $module) {
            if ($module->providesSetupWizard()) {
                $wizards[$name] = $module->getSetupWizard();
            }
        }

        return $wizards;
    }
}
