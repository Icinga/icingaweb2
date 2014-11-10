<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Setup;

use Exception;
use Icinga\Application\Icinga;

class EnableModuleStep extends Step
{
    protected $modulePaths;

    protected $moduleName;

    protected $error;

    public function __construct($moduleName)
    {
        $this->moduleName = $moduleName;

        $this->modulePaths = array();
        if (($appModulePath = realpath(Icinga::app()->getApplicationDir() . '/../modules')) !== false) {
            $this->modulePaths[] = $appModulePath;
        }
    }

    public function apply()
    {
        try {
            $moduleManager = Icinga::app()->getModuleManager();
            $moduleManager->detectInstalledModules($this->modulePaths);
            $moduleManager->enableModule($this->moduleName);
        } catch (Exception $e) {
            $this->error = $e;
            return false;
        }

        $this->error = false;
        return true;
    }

    public function getSummary()
    {
        // Enabling a module is like a implicit action, which does not need to be shown to the user...
    }

    public function getReport()
    {
        if ($this->error === false) {
            return '<p>' . sprintf(t('Module "%s" has been successfully enabled.'), $this->moduleName) . '</p>';
        } elseif ($this->error !== null) {
            $message = t('Module "%s" could not be enabled. An error occured:');
            return '<p class="error">' . sprintf($message, $this->moduleName) . '</p>'
                . '<p>' . $this->error->getMessage() . '</p>';
        }
    }
}
