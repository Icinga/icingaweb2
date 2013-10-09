<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application\Modules;

use Icinga\Application\ApplicationBootstrap;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Data\DataArray\Datasource as ArrayDatasource;
use Icinga\Data\DataArray\Query as ArrayQuery;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\SystemPermissionException;
use Icinga\Exception\ProgrammingError;

/**
 * Module manager that handles detecting, enabling and disabling of modules
 *
 * Modules can have 3 states:
 * * installed, module exists but is disabled
 * * enabled, module enabled and should be loaded
 * * loaded, module enabled and loaded via the autoloader
 *
 */
class Manager
{
    /**
     * Array of all installed module's base directories
     *
     * @var array
     */
    private $installedBaseDirs = array();

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
    private $modulePaths        = array();

    /**
     *  Create a new instance of the module manager
     *
     *  @param ApplicationBootstrap $app
     *  @param string               $enabledDir     Enabled modules location. The application maintains symlinks within
     *                                              the given path
     *  @param array                $availableDirs  Installed modules location
     **/
    public function __construct($app, $enabledDir = null, array $availableDirs = array())
    {
        $this->app = $app;
        if (empty($availableDirs)) {
            $availableDirs = array(ICINGA_APPDIR . '/../modules');
        }
        $this->modulePaths = $availableDirs;
        if ($enabledDir === null) {
            $enabledDir = $this->app->getConfigDir() . '/enabledModules';
        }

        $this->enableDir = $enabledDir;
    }

    /**
     * Query interface for the module manager
     *
     * @return ArrayQuery
     */
    public function select()
    {
        $source = new ArrayDatasource($this->getModuleInfo());
        return $source->select();
    }

    /**
     * Check for enabled modules
     *
     * Update the internal $enabledDirs property with the enabled modules.
     *
     * @throws ConfigurationError If module dir is not a directory or not readable
     */
    private function detectEnabledModules()
    {
        if (!is_dir($this->enableDir)) {
            throw new ConfigurationError(
                'Could not read enabled modules: Module directory is not a directory: ' . $this->enableDir
            );
        }

        if (!is_readable($this->enableDir)) {
            throw new ConfigurationError(
                'Could not read enabled modules: Module directory is not readable: ' . $this->enableDir
            );
        }

        $fh = opendir($this->enableDir);

        $this->enabledDirs = array();
        while (false !== ($file = readdir($fh))) {

            if ($file[0] === '.' || $file === 'README') {
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
            if (!file_exists($dir) || !is_dir($dir)) {
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
     * @param   string  $name       The name of the module to load
     * @param   mixed   $moduleBase Alternative class to use instead of \Icinga\Application\Modules\Module for
     *                              instantiating modules, used for testing
     *
     * @return  self
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
     * @param   string $name                The module to enable
     *
     * @return  self
     * @throws  ConfigurationError          When trying to enable a module that is not installed
     * @throws  SystemPermissionException   When insufficient permissions for the application exist
     */
    public function enableModule($name)
    {
        if (!$this->hasInstalled($name)) {
            throw new ConfigurationError(
                sprintf(
                    'Cannot enable module "%s". Module is not installed.',
                    $name
                )
            );
        }
        clearstatcache(true);
        $target = $this->installedBaseDirs[$name];
        $link = $this->enableDir . '/' . $name;
        if (!is_writable($this->enableDir)) {
            throw new SystemPermissionException(
                'Can not enable module "' . $name . '". '
                . 'Insufficient system permissions for enabling modules.'
            );
        }
        if (file_exists($link) && is_link($link)) {
            return $this;
        }
        if (!@symlink($target, $link)) {
            $error = error_get_last();
            if (strstr($error["message"], "File exists") === false) {
                throw new SystemPermissionException(
                    'Could not enable module "' . $name . '" due to file system errors. '
                    . 'Please check path and mounting points because this is not a permission error. '
                    . 'Primary error was: ' . $error['message']
                );
            }
        }
        $this->enabledDirs[$name] = $link;
        return $this;
    }

    /**
     * Disable the given module and remove it's enabled state
     *
     * @param   string $name                The name of the module to disable
     *
     * @return  self
     *
     * @throws  ConfigurationError          When the module is not installed or it's not a symlink
     * @throws  SystemPermissionException   When the module can't be disabled
     */
    public function disableModule($name)
    {
        if (!$this->hasEnabled($name)) {
            return $this;
        }
        if (!is_writable($this->enableDir)) {
            throw new SystemPermissionException(
                'Could not disable module. Module path is not writable.'
            );
        }
        $link = $this->enableDir . '/' . $name;
        if (!file_exists($link)) {
            throw new ConfigurationError('Could not disable module. The module ' . $name . ' was not found.');
        }
        if (!is_link($link)) {
            throw new ConfigurationError(
                'Could not disable module. The module "' . $name . '" is not a symlink. '
                . 'It looks like you have installed this module manually and moved it to your module folder. '
                . 'In order to dynamically enable and disable modules, you have to create a symlink to '
                . 'the enabled_modules folder'
            );
        }

        if (file_exists($link) && is_link($link)) {
            if (!@unlink($link)) {
                $error = error_get_last();
                throw new SystemPermissionException(
                    'Could not disable module "' . $name . '" due to file system errors. '
                    . 'Please check path and mounting points because this is not a permission error. '
                    . 'Primary error was: ' . $error['message']
                );
            }
        }

        unset($this->enabledDirs[$name]);
        return $this;
    }

    /**
     * Return the directory of the given module as a string, optionally with a given sub directoy
     *
     * @param   string $name    The module name to return the module directory of
     * @param   string $subdir  The sub directory to append to the path
     *
     * @return  string
     *
     * @throws  ProgrammingError When the module is not installed or existing
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
     * @param   string $name The module to check for being installed
     *
     * @return  bool
     */
    public function hasInstalled($name)
    {
        if (!count($this->installedBaseDirs)) {
            $this->detectInstalledModules();
        }
        return array_key_exists($name, $this->installedBaseDirs);
    }

    /**
     * Return true when the given module is in enabled state, otherwise false
     *
     * @param   string $name The module to check for being enabled
     *
     * @return  bool
     */
    public function hasEnabled($name)
    {
        return array_key_exists($name, $this->enabledDirs);
    }

    /**
     * Return true when the module is in loaded state, otherwise false
     *
     * @param   string $name The module to check for being loaded
     *
     * @return  bool
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
     * @param   string $name        The module name to return
     *
     * @return  Module
     * @throws  ProgrammingError    When the module hasn't been loaded
     */
    public function getModule($name)
    {
        if (!$this->hasLoaded($name)) {
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
     * * name, name of the module as a string
     * * path, path where the module is located as a string
     * * enabled, whether the module is enabled or not as a boolean
     * * loaded, whether the module is loaded or not as a boolean
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
        if (count($this->enabledDirs) === 0) {
            $this->detectEnabledModules();
        }

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
     * Return an array of module names from installed modules
     *
     * Calls detectInstalledModules() if no module discovery has been performed yet
     *
     * @return  array
     *
     * @see     detectInstalledModules()
     */
    public function listInstalledModules()
    {
        if (!count($this->installedBaseDirs)) {
            $this->detectInstalledModules();
        }

        if (count($this->installedBaseDirs)) {
            return array_keys($this->installedBaseDirs);
        }

        return array();
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
