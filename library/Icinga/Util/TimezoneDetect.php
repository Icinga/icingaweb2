<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Util;

use DateTimeZone;

/**
 * Retrieve timezone information from cookie
 *
 * @deprecated use `date_default_timezone_get()` to get the user's timezone instead.
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

        if (in_array($_COOKIE[self::$cookieName] ?? null, DateTimeZone::listIdentifiers(), true)) {
            self::$timezoneName = $_COOKIE[self::$cookieName];
            self::$success = true;
        } else {
            self::$success = false;
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
