<?php

namespace Icinga\Application;

use Zend_Config_Ini;
use Zend_Config;

class Config extends Zend_Config_Ini
{
    protected $data;
    protected static $instance;
    protected $configDir;

    public function listAll($what)
    {
        if ($this->$what === null) {
            return array();
        } else {
            return array_keys($this->$what->toArray());
        }
    }

    public function getConfigDir()
    {
        return $this->configDir;
    }

    public function __construct($filename, $section = null, $options = false)
    {
        $options['allowModifications'] = true;
        $this->configDir = dirname($filename);
        return parent::__construct($filename, $section, $options);
    }

    public function getModuleConfig($key, $module)
    {
        $manager = Icinga::app()->moduleManager();
        $res = null;
        if ($manager->hasInstalled($module)) {
            $filename = $manager->getModuleConfigDir($module) . "/$key.ini";
            if (file_exists($filename)) {
                return $this->$key = new Config($filename);
            }
        }
        return $res;
    }

    public function __get($key)
    {
        $res = parent::__get($key);
        if ($res === null) {
            $app = Icinga::app();
            if ($app->hasModule($key)) {
                $filename = $app->getModule($key)->getConfigDir() . "/$key.ini";
            } else {
                $filename = $this->configDir . '/' . $key . '.ini';
            }
            if (file_exists($filename)) {
                $res = $this->$key = new Config($filename);
            }
        }
        return $res;
    }

    public static function getInstance($configFile = null)
    {
        if (self::$instance === null) {
            if ($configFile === null) {
                $configFile = dirname(dirname(dirname(dirname(__FILE__))))
                            . '/config/icinga.ini';
            }
            self::$instance = new Config($configFile);
        }
        return self::$instance;
    }
}
