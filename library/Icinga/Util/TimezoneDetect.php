<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

use Icinga\Application\Platform;

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
     * Timezone offset in minutes
     *
     * @var int
     */
    private static $offset = 0;

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
     * Timezone name
     *
     * @var string
     */
    private static $timezone;

    /**
     * Create new object and try to identify the timezone
     */
    public function __construct()
    {
        if (self::$success !== null) {
            return;
        }

        if (Platform::isCli() === false && array_key_exists(self::$cookieName, $_COOKIE)) {
            $cookieValue = $_COOKIE[self::$cookieName];
            list($offset, $dst) = explode(
                strpos($cookieValue, ',') === false ? '-' : ',',
                $cookieValue
            );
            $timezoneName = timezone_name_from_abbr('', (int)$offset, (int)$dst);

            self::$success = (bool)$timezoneName;

            if (self::$success === true) {
                self::$offset = $offset;
                self::$timezoneName = $timezoneName;
            }
        }
    }

    /**
     * Get offset
     *
     * @return int
     */
    public function getOffset()
    {
        return self::$offset;
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
        self::$offset = 0;
    }
}
