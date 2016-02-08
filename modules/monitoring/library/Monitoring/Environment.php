<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring;

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
        return Hook::createInstance('grapher', null, self::config($env, 'grapher'));
    }

    public static function configBackend($env = null)
    {
        return Hook::createInstance(
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
