<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Utils;

use Exception;
use Icinga\Application\Icinga;
use Icinga\Module\Setup\Step;

class EnableModuleStep extends Step
{
    protected $modulePaths;

    protected $moduleNames;

    protected $errors;

    public function __construct(array $moduleNames)
    {
        $this->moduleNames = $moduleNames;

        $this->modulePaths = array();
        if (($appModulePath = realpath(Icinga::app()->getApplicationDir() . '/../modules')) !== false) {
            $this->modulePaths[] = $appModulePath;
        }
    }

    public function apply()
    {
        $moduleManager = Icinga::app()->getModuleManager();
        $moduleManager->detectInstalledModules($this->modulePaths);

        $success = true;
        foreach ($this->moduleNames as $moduleName) {
            try {
                $moduleManager->enableModule($moduleName);
            } catch (Exception $e) {
                $this->errors[$moduleName] = $e;
                $success = false;
            }
        }

        return $success;
    }

    public function getSummary()
    {
        // Enabling a module is like a implicit action, which does not need to be shown to the user...
    }

    public function getReport()
    {
        $okMessage = mt('setup', 'Module "%s" has been successfully enabled.');
        $failMessage = mt('setup', 'Module "%s" could not be enabled. An error occured:');

        $report = '';
        foreach ($this->moduleNames as $moduleName) {
            if (isset($this->errors[$moduleName])) {
                $report .= '<p class="error">' . sprintf($failMessage, $moduleName) . '</p>'
                    . '<p>' . $this->errors[$moduleName]->getMessage() . '</p>';
            } else {
                $report .= '<p>' . sprintf($okMessage, $moduleName) . '</p>';
            }
        }

        return $report;
    }
}
