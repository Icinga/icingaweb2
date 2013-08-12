<?php

namespace Icinga\Application\Modules;

use Icinga\Application\Icinga;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\SystemPermissionException;
use Icinga\Exception\ProgrammingError;

/**
 * Module manager that handles detecting, enabling and disabling of modules
 *
 * Modules can have 3 states:
 *      - installed         Means that the module exists, but could be deactivated (see enabled and loaded)
 *      - enabled           Means that the module is marked as being enabled and should be used
 *      - loaded            Means that the module has been registered in the autoloader and is being used
 *
 */
class Manager
{
    /**
     * Array of all installed module's base directories
     *
     * null if modules haven't been scanned yet
     *
     * @var array|null
     */
    protected $installedBaseDirs = null;

    /**
     * Array of all enabled modules base dirs
     *
     * @var array
     */
    protected $enabledDirs       = array();

    /**
     * Array of all module names that have been loaded
     *
     * @var array
     */
    protected $loadedModules     = array();

    /**
     * Reference to Icinga::app
     *
     * @var Icinga
     */
    protected $app;

    /**
     * The directory that is used to detect enabled modules
     *
     * @var string
     */
    protected $enableDir;

    /**
     * All paths to look for installed modules that can be enabled
     *
     * @var array
     */
    protected $modulePaths = array();

    /**
     * Creates a new instance of the module manager to
     *
     *  @param $app :   The applicaiton bootstrap. This one needs a properly defined interface
    *                   In order to test it correctly, the application now only requires a stdClass
    *   @param $dir :   
    **/
    public function __construct($app, $enabledDir = null, array $availableDirs = array())
    {
        $this->app = $app;
        if (empty($availableDirs)) {
            $availableDirs = array(ICINGA_APPDIR."/../modules");
        }
        $this->modulePaths = $availableDirs;
        if ($enabledDir === null) {
            $enabledDir = $this->app->getConfig()->getConfigDir()
                         . '/enabledModules';
        }
        $this->prepareEssentials($enabledDir);
        $this->detectEnabledModules();
    }

    protected function prepareEssentials($moduleDir)
    {
        $this->enableDir = $moduleDir;

        if (! file_exists($this->enableDir) || ! is_dir($this->enableDir)) {
            throw new ProgrammingError(
                sprintf(
                    'Missing module directory: %s',
                    $this->enableDir
                )
            );
        }
    }

    public function select()
    {
        $source = new \Icinga\Data\ArrayDatasource($this->getModuleInfo());
        return $source->select();
    }

    protected function detectEnabledModules()
    {
        $fh = opendir($this->enableDir);

        $this->enabledDirs = array();
        while (false !== ($file = readdir($fh))) {

            if ($file[0] === '.') {
                continue;
            }

            $link = $this->enableDir . '/' . $file;
            if (! is_link($link)) {
                continue;
            }

            $dir = realpath($link);
            if (! file_exists($dir) || ! is_dir($dir)) {
                continue;
            }

            $this->enabledDirs[$file] = $dir;
        }
    }

    public function loadEnabledModules()
    {
        foreach ($this->listEnabledModules() as $name) {
            $this->loadModule($name);
        }
        return $this;
    }

    public function loadModule($name, $moduleBase = null)
    {
        if ($this->hasLoaded($name)) {
            return $this;
        }

        $module = null;
        if ($moduleBase === null) {
            $module = new Module($this->app, $name, $this->getModuleDir($name));
        } else {
            $module = new $moduleBase($this->app, $name, $this->getModuleDir($name));
        }
        $module->register();
        $this->loadedModules[$name] = $module;
        return $this;
    }

    public function enableModule($name)
    {
        if (! $this->hasInstalled($name)) {
            throw new ConfigurationError(
                sprintf(
                    "Cannot enable module '%s' as it isn't installed",
                    $name
                )
            );
            return $this;
        }
        clearstatcache(true);
        $target = $this->installedBaseDirs[$name];
        $link = $this->enableDir . '/' . $name;
        if (! is_writable($this->enableDir)) {
            throw new SystemPermissionException(
                "Insufficient system permissions for enabling modules",
                "write",
                $this->enableDir
            );
        }
        if (file_exists($link) && is_link($link)) {
            return $this;
        }
        if (!@symlink($target, $link)) {
            $error = error_get_last();
            if (strstr($error["message"], "File exists") === false) {
                throw new SystemPermissionException($error["message"], "symlink", $link);
            }
        }
        $this->enabledDirs[$name] = $link;
        return $this;
    }

    public function disableModule($name)
    {
        if (! $this->hasEnabled($name)) {
            return $this;
        }
        if (! is_writable($this->enableDir)) {
            throw new SystemPermissionException("Can't write the module directory", "write", $this->enableDir);
            return $this;
        }
        $link = $this->enableDir . '/' . $name;
        if (!file_exists($link)) {
            throw new ConfigurationError("The module $name could not be found, can't disable it");
        }
        if (!is_link($link)) {
            throw new ConfigurationError(
                "The module $name can't be disabled as this would delete the whole module. ".
                "It looks like you have installed this module manually and moved it to your module folder.".
                "In order to dynamically enable and disable modules, you have to create a symlink to ".
                "the enabled_modules folder"
            );
        }
            
        if (file_exists($link) && is_link($link)) {
            if (!@unlink($link)) {
                $error = error_get_last();
                throw new SystemPermissionException($error["message"], "unlink", $link);
            }
        } else {

        }
        unset($this->enabledDirs[$name]);
        return $this;
    }

    public function getModuleConfigDir($name)
    {
        return $this->getModuleDir($name, '/config');
    }

    public function getModuleDir($name, $subdir = '')
    {
        if ($this->hasEnabled($name)) {
            return $this->enabledDirs[$name]. $subdir;
        }

        if ($this->hasInstalled($name)) {
            return $this->installedBaseDirs[$name] . $subdir;
        }

        throw new ProgrammingError(
            sprintf(
                'Trying to access uninstalled module dir: %s',
                $name
            )
        );
    }

    public function hasInstalled($name)
    {
        if ($this->installedBaseDirs === null) {
            $this->detectInstalledModules();
        }
        return array_key_exists($name, $this->installedBaseDirs);
    }

    public function hasEnabled($name)
    {
        return array_key_exists($name, $this->enabledDirs);
    }

    public function hasLoaded($name)
    {
        return array_key_exists($name, $this->loadedModules);
    }

    public function getLoadedModules()
    {
        return $this->loadedModules;
    }

    public function getModule($name)
    {
        if (! $this->hasLoaded($name)) {
            throw new ProgrammingError(
                sprintf(
                    'Cannot access module %s as it hasn\'t been loaded',
                    $name
                )
            );
        }
        return $this->loadedModules[$name];
    }

    public function getModuleInfo()
    {
        $installed = $this->listInstalledModules();
        
        $info = array();
        if ($installed === null) {
            return $info;
        }
        
        foreach ($installed as $name) {
            $info[] = (object) array(
                'name'    => $name,
                'path'    => $this->installedBaseDirs[$name],
                'enabled' => $this->hasEnabled($name),
                'loaded'  => $this->hasLoaded($name)
            );
        }
        return $info;
    }

    public function listEnabledModules()
    {
        return array_keys($this->enabledDirs);
    }

    public function listLoadedModules()
    {
        return array_keys($this->loadedModules);
    }

    public function listInstalledModules()
    {
        if ($this->installedBaseDirs === null) {
            $this->detectInstalledModules();
        }
        
        if ($this->installedBaseDirs !== null) {
            return array_keys($this->installedBaseDirs);
        }
    }

    public function detectInstalledModules()
    {
        foreach ($this->modulePaths as $basedir) {
            $fh = opendir($basedir);
            if ($fh === false) {
                return $this;
            }
    
            while ($name = readdir($fh)) {
                if ($name[0] === '.') {
                    continue;
                }
                if (is_dir($basedir . '/' . $name)) {
                    $this->installedBaseDirs[$name] = $basedir . '/' . $name;
                }
            }
        }
    }

}
