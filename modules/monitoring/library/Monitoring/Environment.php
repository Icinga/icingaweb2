<?php

namespace Icinga\Module\Monitoring;

use \Icinga\Application\Config;
use Icinga\Web\Session;
use Exception;

class Environment
{
    protected static $envs = array(
        'default' => array(
            'backend'       => null,
            'grapher'       => null,
            'configBackend' => null,
            'commandPipe'   => null,
        )
    );

    public static function defaultName()
    {
        // TODO: Check session
        reset(self::$envs);
        return key(self::$envs);
    }

    protected static function config($env, $what)
    {
        return self::$config[self::getName($env)][$what];
    }

    protected static function getName($env)
    {
        return $env === null ? self::defaultName() : $env;
    }

    public static function backend($env = null)
    {
        return Backend::getInstance(self::config($env, 'backend'));
    }

    public static function grapher($env = null)
    {
        return Hook::get('grapher', null, self::config($env, 'grapher'));
    }

    public static function configBackend($env = null)
    {
        return Hook::get(
            'configBackend',
            null,
            self::config($env, 'configBackend')
        );
    }

    public static function commandPipe($env = null)
    {
        return CommandPipe::getInstance(self::config($env, 'commandPipe'));
    }
}
