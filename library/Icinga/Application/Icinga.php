<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

use Icinga\Exception\ProgrammingError;

/**
 * Icinga application container
 */
class Icinga
{
    /**
     * @var ApplicationBootstrap
     */
    private static $app;

    /**
     * Getter for an application environment
     *
     * @return ApplicationBootstrap|Web
     * @throws ProgrammingError
     */
    public static function app()
    {
        if (self::$app == null) {
            throw new ProgrammingError('Icinga has never been started');
        }

        return self::$app;
    }

    /**
     * Setter for an application environment
     *
     * @param   ApplicationBootstrap    $app
     * @param   bool                    $overwrite
     *
     * @throws ProgrammingError
     */
    public static function setApp(ApplicationBootstrap $app, $overwrite = false)
    {
        if (self::$app !== null && !$overwrite) {
            throw new ProgrammingError('Cannot start Icinga twice');
        }

        self::$app = $app;
    }
}
