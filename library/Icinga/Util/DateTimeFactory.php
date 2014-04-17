<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Util;

use Exception;
use DateTime;
use DateTimeZone;
use Icinga\Util\ConfigAwareFactory;
use Icinga\Exception\ConfigurationError;

/**
 * Factory for time zone aware DateTime objects
 */
class DateTimeFactory implements ConfigAwareFactory
{
    /**
     * Time zone used throughout DateTime object creation
     * @var DateTimeZone
     */
    private static $timeZone;

    /**
     * Set the factory's config
     *
     * Set the factory's time zone via key timezone in the given config array
     *
     * @param   array   $config
     * @throws  \Icinga\Exception\ConfigurationError if the given config is not valid
     */
    public static function setConfig($config)
    {
        try {
            $tz = new DateTimeZone(isset($config['timezone']) ? $config['timezone'] : '');
        } catch (Exception $e) {
            throw new ConfigurationError('"DateTimeFactory" expects a valid time zone be set via "setConfig"');
        }

        self::$timeZone = $tz;
    }

    /**
     * Return new DateTime object using the given format, time and set time zone
     *
     * Wraps DateTime::createFromFormat()
     *
     * @param   string          $format
     * @param   string          $time
     * @param   DateTimeZone    $timeZone
     * @return  DateTime
     * @see     DateTime::createFromFormat()
     */
    public static function parse($time, $format, DateTimeZone $timeZone = null)
    {
        return DateTime::createFromFormat($format, $time, $timeZone !== null ? $timeZone : self::$timeZone);
    }

    /**
     * Return new DateTime object using the given date/time string and set time zone
     *
     * Wraps DateTime::__construct()
     *
     * @param   string          $time
     * @param   DateTimeZone    $timeZone
     * @return  DateTime
     * @see     DateTime::__construct()
     */
    public static function create($time = 'now', DateTimeZone $timeZone = null)
    {
        return new DateTime($time, $timeZone !== null ? $timeZone : self::$timeZone);
    }
}
