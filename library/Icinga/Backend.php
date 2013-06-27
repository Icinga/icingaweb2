<?php

namespace Icinga;

use Icinga\Application\Config;
use Icinga\Authentication\Manager as AuthManager;

class Backend
{
    protected static $instances = array();

    protected function __construct() {}

    public static function getInstance($name = null)
    {
        if (! array_key_exists($name, self::$instances)) {
            $config = Config::getInstance()->backends;
            if ($name === null) {
                $name = AuthManager::getInstance()->getSession()->get('backend');
            }
            if ($name === null) {
                $name = array_shift(array_keys($config->toArray()));
            }
            if (isset($config->backends->$name)) {
                $config = $config->backends->$name;
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

