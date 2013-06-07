<?php

namespace Icinga\Application;

use Icinga\Exception\ProgrammingError;

class Icinga
{
    protected static $app;

    public static function app()
    {
        if (null === self::$app) {
            throw new ProgrammingError('Icinga has never been started');
        }
        return self::$app;
    }

    public static function setApp($app)
    {
        if (null !== self::$app) {
            throw new ProgrammingError('Cannot start Icinga twice');
        }
        self::$app = $app;
    }
}
