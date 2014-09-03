<?php
// {{{ICINGA_LICENSE_HEADER}}}
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
     *
     * @var DateTimeZone
     */
    protected static $timeZone;

    /**
     * Set the factory's config
     *
     * Set the factory's time zone via key timezone in the given config array
     *
     * @param   array               $config     An array with key 'timezone'
     *
     * @throws  ConfigurationError              if the given array misses the key 'timezone'
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
     * Return new DateTime object using the given format, time and set timezone
     *
     * Wraps DateTime::createFromFormat()
     *
     * @param   string          $format
     * @param   string          $time
     * @param   DateTimeZone    $timeZone
     *
     * @return  DateTime
     *
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
     *
     * @return  DateTime
     *
     * @see     DateTime::__construct()
     */
    public static function create($time = 'now', DateTimeZone $timeZone = null)
    {
        return new DateTime($time, $timeZone !== null ? $timeZone : self::$timeZone);
    }

    /**
     * Check whether a variable is a Unix timestamp
     *
     * @param   mixed $timestamp
     *
     * @return  bool
     */
    public static function isUnixTimestamp($timestamp)
    {
        return (is_int($timestamp) || ctype_digit($timestamp))
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);
    }
}
