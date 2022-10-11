<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Test;

use Icinga\Application\Cli;

class Bootstrap
{
    public static function bootstrap(string $module, string $basedir, string $testsDir = null)
    {
        error_reporting(E_ALL | E_STRICT);

        $configDir = getenv('ICINGAWEB_CONFIGDIR');
        if (isset($_SERVER['ICINGAWEB_CONFIGDIR'])) {
            $configDir = $_SERVER['ICIGNAWEB_CONFIGDIR'];
        }

        if (! $testsDir) {
            $testsDir = $basedir . '/tests';
        }

        require_once 'Icinga/Application/Cli.php';

        Cli::start($testsDir, $configDir)
            ->getModuleManager()
            ->loadModule($module, $basedir);
    }
}
