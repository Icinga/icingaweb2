<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Zend_Config_Ini;

/**
 * Global registry of application and module configuration.
 */
class Config extends Zend_Config_Ini
{
    /**
     * Configuration directory where ALL (application and module) configuration is located.
     *
     * @var string
     */
    public static $configDir;

    /**
     * The INI file this configuration has been loaded from.
     *
     * @var string
     */
    protected $configFile;

    /**
     * Application config instances per file.
     *
     * @var array
     */
    protected static $app = array();

    /**
     * Module config instances per file.
     *
     * @var array
     */
    protected static $modules = array();

    /**
     * Load configuration from the config file $filename.
     *
     * @see     Zend_Config_Ini::__construct
     *
     * @param   string      $filename
     * @throws  \Exception
     */
    public function __construct($filename)
    {
        if (!@is_readable($filename)) {
            throw new \Exception('Cannot read config file: ' . $filename);
        };
        $this->configFile = $filename;
        parent::__construct($filename);
    }

    /**
     * Retrieve a application config instance.
     *
     * @param   string  $configname
     * @return  mixed
     */
    public static function app($configname = 'config')
    {
        if (!isset(self::$app[$configname])) {
            $filename = self::$configDir . '/' . $configname . '.ini';
            self::$app[$configname] = new Config($filename);
        }
        return self::$app[$configname];
    }

    /**
     * Retrieve a module config instance.
     *
     * @param   string  $modulename
     * @param   string  $configname
     * @return  Config
     */
    public static function module($modulename, $configname = 'config')
    {
        if (!isset(self::$modules[$modulename])) {
            self::$modules[$modulename] = array();
        }
        $moduleConfigs = self::$modules[$modulename];
        if (!isset($moduleConfigs[$configname])) {
            $filename = self::$configDir . '/modules/' . $modulename . '/' . $configname . '.ini';
            $moduleConfigs[$configname] = new Config($filename);
        }
        return $moduleConfigs[$configname];
    }

    /**
     * Retrieve names of accessible sections or properties.
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
}
