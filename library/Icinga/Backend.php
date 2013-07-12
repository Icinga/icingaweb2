<?php

namespace Icinga;

use Icinga\Application\Config as IcingaConfig;
use Icinga\Authentication\Manager as AuthManager;

class Backend
{
    protected static $instances = array();

    protected function __construct() {}

    public static function getInstance($name = null)
    {
        if (! array_key_exists($name, self::$instances)) {
            $backends = IcingaConfig::app('backends');
            if ($name === null) {
                $name = AuthManager::getInstance()->getSession()->get('backend');
            }
            if ($name === null) {
                $name = array_shift(array_keys($backends->toArray()));
            }
            if (isset($backends->$name)) {
                $config = $backends->$name;
                $type = $config->type;
                $type[0] = strtoupper($type[0]);
                $class = '\\Icinga\\Backend\\' . $type;
                self::$instances[$name] = new $class($config);
            } else {
                throw new \Exception(sprintf(
                    'Got no config for backend %s',
                    $name
                ));
            }
        }
        return self::$instances[$name];
    }
}

