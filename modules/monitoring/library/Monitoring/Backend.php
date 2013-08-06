<?php

namespace Monitoring;

use Icinga\Config\Config as IcingaConfig;
use Icinga\Authentication\Manager as AuthManager;
use Exception;

class Backend
{
    protected static $instances = array();
    protected static $backendConfigs;

    final protected function __construct()
    {
    }

    public static function exists($name)
    {
        $configs = self::getBackendConfigs();
        return array_key_exists($name, $configs);
    }

    public static function getDefaultName()
    {
        $configs = self::getBackendConfigs();
        if (empty($configs)) {
            throw new Exception(
                'Cannot get default backend as no backend has been configured'
            );
        }
        reset($configs);
        return key($configs);
    }

    public static function getBackendConfigs()
    {
        if (self::$backendConfigs === null) {
            $backends = IcingaConfig::app('backends');
            foreach ($backends as $name => $config) {
                // TODO: Check if access to this backend is allowed
                self::$backendConfigs[$name] = $config;
            }
        }
        return self::$backendConfigs;
    }

    public static function getBackend($name = null)
    {
        if (! array_key_exists($name, self::$instances)) {
            if ($name === null) {
                $name = self::getDefaultName();
            } else {
                if (! self::exists($name)) {
                    throw new Exception(sprintf(
                        'There is no such backend: "%s"',
                        $name
                    ));
                }
            }

            $config = self::$backendConfigs[$name];
            $type = $config->type;
            $type[0] = strtoupper($type[0]);
            $class = '\\Monitoring\\Backend\\' . $type;
            self::$instances[$name] = new $class($config);
        }
        return self::$instances[$name];
    }

    public static function getInstance($name = null)
    {
        if (array_key_exists($name, self::$instances)) {
            return self::$instances[$name];
        } else {
            if ($name === null) {
                // TODO: Remove this, will be chosen by Environment
                $name = AuthManager::getInstance()->getSession()->get('backend');
            }
            return self::getBackend($name);
        }
    }
}
