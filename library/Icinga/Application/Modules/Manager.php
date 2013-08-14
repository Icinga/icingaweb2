<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application\Modules;

use \Icinga\Application\ApplicationBootstrap;
use \Icinga\Application\Icinga;
use \Icinga\Application\Logger;
use \Icinga\Exception\ConfigurationError;
use \Icinga\Exception\SystemPermissionException;
use \Icinga\Exception\ProgrammingError;

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
    private $installedBaseDirs = null;

    /**
     * Array of all enabled modules base dirs
     *
     * @var array
     */
    private $enabledDirs       = array();

    /**
     * Array of all module names that have been loaded
     *
     * @var array
     */
    private $loadedModules     = array();

    /**
     * Reference to Icinga::app
     *
     * @var Icinga
     */
    private $app;

    /**
     * The directory that is used to detect enabled modules
     *
     * @var string
     */
    private $enableDir;

    /**
     * All paths to look for installed modules that can be enabled
     *
     * @var array
     */
    private $modulePaths = array();

    /**
     *  Create a new instance of the module manager
     *
     *  @param ApplicationBootstrap $app    The application bootstrap. This one needs a properly defined interface
     *                                      In order to test it correctly, the application now only requires a stdClass
     *  @param string $enabledDir           The path of the dir used for adding symlinks to enabled modules
     *                                      ( must be writable )
     *  @param array $availableDirs         An array containing all paths where the modulemanager can look for available
     *                                      modules
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

    /**
     * Set the module dir and checks for existence
     *
     * @param string $moduleDir                     The module directory to set for the module manager
     * @throws \Icinga\Exception\ProgrammingError
     */
    private function prepareEssentials($moduleDir)
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

    /**
     * Query interface for the module manager
     *
     * @return \Icinga\Data\ArrayQuery
     */
    public function select()
    {
        $source = new \Icinga\Data\ArrayDatasource($this->getModuleInfo());
        return $source->select();
    }

    /**
     *  Check for enabled modules and update the internal $enabledDirs property with the enabled modules
     *
     */
    private function detectEnabledModules()
    {
        $fh = opendir($this->enableDir);

        $this->enabledDirs = array();
        while (false !== ($file = readdir($fh))) {

            if ($file[0] === '.') {
                continue;
            }

            $link = $this->enableDir . '/' . $file;
            if (! is_link($link)) {
                Logger::warn(
                    'Found invalid module in enabledModule directory "%s": "%s" is not a symlink',
                    $this->enableDir,
                    $link
                );
                continue;
            }

            $dir = realpath($link);
            if (! file_exists($dir) || ! is_dir($dir)) {
                Logger::warn(
                    'Found invalid module in enabledModule directory "%s": "%s" points to non existing path "%s"',
                    $this->enableDir,
                    $link,
                    $dir
                );
                continue;
            }

            $this->enabledDirs[$file] = $dir;
        }
    }

    /**
     * Try to set all enabled modules in loaded sate
     *
     * @return  self
     * @see     Manager::loadModule()
     */
    public function loadEnabledModules()
    {
        foreach ($this->listEnabledModules() as $name) {
            $this->loadModule($name);
        }
        return $this;
    }

    /**
     * Try to load the module and register it in the application
     *
     * @param string $name              The name of the module to load
     * @param null|mixed $moduleBase    An alternative class to use instead of @see Module, used for testing
     *
     * @return self
     */
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

    /**
     * Set the given module to the enabled state
     *
     * @param string $name                                  The module to enable
     *
     * @return self
     * @throws \Icinga\Exception\ConfigurationError         When trying to enable a module that is not installed
     * @throws \Icinga\Exception\SystemPermissionException  When insufficient permissions for the application exist
     */
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

    /**
     * Disable the given module and remove it's enabled state
     *
     * @param string $name                                  The name of the module to disable
     *
     * @return self
     * @throws \Icinga\Exception\ConfigurationError         When the module is not installed or it's not symlinked
     * @throws \Icinga\Exception\SystemPermissionException  When the module can't be disabled
     */
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

    /**
     * Return the directory of the given module as a string, optionally with a given sub directoy
     *
     * @param string $name                          The module name to return the module directory of
     * @param string $subdir                        The sub directory to append to the path
     *
     * @return string
     * @throws \Icinga\Exception\ProgrammingError   When the module is not installed or existing
     */
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

    /**
     * Return true when the module with the given name is installed, otherwise false
     *
     * @param string $name          The module to check for being installed
     * @return bool
     */
    public function hasInstalled($name)
    {
        if ($this->installedBaseDirs === null) {
            $this->detectInstalledModules();
        }
        return array_key_exists($name, $this->installedBaseDirs);
    }

    /**
     * Return true when the given module is in enabled state, otherwise false
     *
     * @param string $name          The module to check for being enabled
     *
     * @return bool
     */
    public function hasEnabled($name)
    {
        return array_key_exists($name, $this->enabledDirs);
    }

    /**
     * Return true when the module is in loaded state, otherwise false
     *
     * @param string $name          The module to check for being loaded
     *
     * @return bool
     */
    public function hasLoaded($name)
    {
        return array_key_exists($name, $this->loadedModules);
    }

    /**
     * Return an array containing all loaded modules
     *
     * @return  array
     * @see     Module
     */
    public function getLoadedModules()
    {
        return $this->loadedModules;
    }

    /**
     * Return the module instance of the given module when it is loaded
     *
     * @param string $name                          The module name to return
     * @return Module
     *
     * @throws \Icinga\Exception\ProgrammingError   Thrown when the module hasn't been loaded
     */
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

    /**
     * Return an array containing information objects for each available module
     *
     * Each entry has the following fields
     *  - name:     The name of the module as a string
     *  - path:     The path where the module is located as a string
     *  - enabled:  Whether the module is enabled or not as a boolean
     *  - loaded:   Whether the module is loaded or not as a boolean
     *
     * @return array
     */
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

    /**
     * Return an array containing all enabled module names as strings
     *
     * @return array
     */
    public function listEnabledModules()
    {
        return array_keys($this->enabledDirs);
    }

    /**
     * Return an array containing all loaded module names as strings
     *
     * @return array
     */
    public function listLoadedModules()
    {
        return array_keys($this->loadedModules);
    }

    /**
     * Return an array containing all installled module names as strings
     *
     * Calls @see Manager::detectInstalledModules if no module discovery has
     * been performed yet
     *
     * @return array
     */
    public function listInstalledModules()
    {
        if ($this->installedBaseDirs === null) {
            $this->detectInstalledModules();
        }
        
        if ($this->installedBaseDirs !== null) {
            return array_keys($this->installedBaseDirs);
        }
    }

    /**
     * Detect installed modules from every path provided in modulePaths
     *
     * @return self
     */
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
        return $this;
    }
}
