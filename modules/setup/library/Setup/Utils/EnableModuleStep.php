<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Utils;

use Exception;
use Icinga\Application\Icinga;
use Icinga\Exception\IcingaException;
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

        $report = array();
        foreach ($this->moduleNames as $moduleName) {
            if (isset($this->errors[$moduleName])) {
                $report[] = sprintf($failMessage, $moduleName);
                $report[] = sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->errors[$moduleName]));
            } else {
                $report[] = sprintf($okMessage, $moduleName);
            }
        }

        return $report;
    }
}
