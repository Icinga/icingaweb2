<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

/**
 * Retrieve timezone information from cookie
 */
class TimezoneDetect
{
    /**
     * If detection was successful
     *
     * @var bool
     */
    private static $success;

    /**
     * @var string
     */
    private static $timezoneName;

    /**
     * Cookie name
     *
     * @var string
     */
    public static $cookieName = 'icingaweb2-tzo';

    /**
     * Create new object and try to identify the timezone
     */
    public function __construct()
    {
        if (self::$success !== null) {
            return;
        }

        if (array_key_exists(self::$cookieName, $_COOKIE)) {
            self::$timezoneName = $_COOKIE[self::$cookieName];
            self::$success = true;
        }
    }

    /**
     * Get timezone name
     *
     * @return string
     */
    public function getTimezoneName()
    {
        return self::$timezoneName;
    }

    /**
     * True on success
     *
     * @return bool
     */
    public function success()
    {
        return self::$success;
    }

    /**
     * Reset object
     */
    public function reset()
    {
        self::$success = null;
        self::$timezoneName = null;
    }
}
