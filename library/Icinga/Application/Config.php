<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Zend_Config;
use Zend_Config_Ini;
use Icinga\Exception\NotReadableError;

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
     * The INI file this configuration has been loaded from or should be written to
     *
     * @var string
     */
    protected $configFile;

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

    /**
     * Load configuration from the given INI file
     *
     * @param   string      $file   The file to parse
     *
     * @throws  NotReadableError    When the file does not exist or cannot be read
     */
    public static function fromIni($file)
    {
        $config = new static(array(), true);
        $filepath = realpath($file);

        if ($filepath === false) {
            $config->setConfigFile($file);
        } elseif (is_readable($filepath)) {
            $config->setConfigFile($filepath);
            $config->merge(new Zend_Config_Ini($filepath));
        } else {
            throw new NotReadableError('Cannot read config file "%s". Permission denied', $filepath);
        }

        return $config;
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
            self::$app[$configname] = Config::fromIni(self::resolvePath($configname . '.ini'));
        }
        return self::$app[$configname];
    }

    /**
     * Set module config
     *
     * @param string        $moduleName
     * @param string        $configName
     * @param Zend_Config   $config
     */
    public static function setModuleConfig($moduleName, $configName, Zend_Config $config)
    {
        self::$modules[$moduleName][$configName] = $config;
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
            $moduleConfigs[$configname] = Config::fromIni(
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
     * Return this config's file path
     *
     * @return  string
     */
    public function getConfigFile()
    {
        return $this->configFile;
    }

    /**
     * Set this config's file path
     *
     * @param   string      $filepath   The path to the config file
     *
     * @return  self
     */
    public function setConfigFile($filepath)
    {
        $this->configFile = $filepath;
        return $this;
    }

    /**
     * Prepend configuration base dir if input is relative
     *
     * @param   string  $path   Input path
     * @return  string          Absolute path
     */
    public static function resolvePath($path)
    {
        return self::$configDir . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }
}
