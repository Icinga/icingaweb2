<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Zend_Config;
use Zend_Config_Ini;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\ProgrammingError;

/**
 * Global registry of application and module configuration.
 */
class Config extends Zend_Config
{
    /**
     * Configuration directory where ALL (application and module) configuration is located
     *
     * @var string
     */
    public static $configDir;

    /**
     * The INI file this configuration has been loaded from
     *
     * @var string
     */
    private $configFile;

    /**
     * Application config instances per file
     *
     * @var array
     */
    protected static $app = array();

    /**
     * Module config instances per file
     *
     * @var array
     */
    protected static $modules = array();

    private $instance;

    /**
     * Load configuration from the config file $filename
     *
     * @param   string $filename    The filename to parse

     * @throws  NotReadableError    When the file does not exist or cannot be read
     */
    public function __construct($filename)
    {
        parent::__construct(array(), true);
        $filepath = realpath($filename);
        if ($filepath === false) {
            $this->configFile = $filename;
        } elseif (is_readable($filepath)) {
            $this->configFile = $filepath;
            $this->merge(new Zend_Config_Ini($filepath));
        } else {
            throw new NotReadableError('Cannot read config file "' . $filename . '". Permission denied');
        }
    }

    /**
     * Retrieve a application config instance
     *
     * @param   string  $configname     The configuration name (without ini suffix) to read and return
     * @param   bool    $fromDisk       When set true, the configuration will be read from the disk, even
     *                                  if it already has been read
     *
     * @return  Config                  The configuration object that has been requested
     */
    public static function app($configname = 'config', $fromDisk = false)
    {
        if (!isset(self::$app[$configname]) || $fromDisk) {
            self::$app[$configname] = new Config(self::resolvePath($configname . '.ini'));
        }
        return self::$app[$configname];
    }

    /**
     * Retrieve a module config instance
     *
     * @param   string  $modulename     The name of the module to look for configurations
     * @param   string  $configname     The configuration name (without ini suffix) to read and return
     * @param   string  $fromDisk       Whether to read the configuration from disk
     *
     * @return  Config                  The configuration object that has been requested
     */
    public static function module($modulename, $configname = 'config', $fromDisk = false)
    {
        if (!isset(self::$modules[$modulename])) {
            self::$modules[$modulename] = array();
        }
        $moduleConfigs = self::$modules[$modulename];
        if (!isset($moduleConfigs[$configname]) || $fromDisk) {
            $moduleConfigs[$configname] = new Config(
                self::resolvePath('modules/' . $modulename . '/' . $configname . '.ini')
            );
        }
        return $moduleConfigs[$configname];
    }

    /**
     * Retrieve names of accessible sections or properties
     *
     * @param   $name
     * @return  array
     */
    public function keys($name = null)
    {
        if ($name === null) {
            return array_keys($this->toArray());
        } elseif ($this->$name === null) {
            return array();
        } else {
            return array_keys($this->$name->toArray());
        }
    }

    /**
     * Return the application wide config file
     *
     * @return string
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * Prepend configuration base dir if input is relative
     *
     * @param   string  $path   Input path
     * @return  string          Absolute path
     */
    public static function resolvePath($path)
    {
        if (strpos($path, DIRECTORY_SEPARATOR) === 0 || self::$configDir === false) {
            return $path;
        }

        return self::$configDir . DIRECTORY_SEPARATOR . $path;
    }
}
