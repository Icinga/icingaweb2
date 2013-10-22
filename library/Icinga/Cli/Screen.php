<?php

namespace Icinga\Cli;

use Icinga\Cli\Screen\AnsiScreen;

class Screen
{
    protected static $instance;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new AnsiScreen();
        }
        return self::$instance;
    }
}
