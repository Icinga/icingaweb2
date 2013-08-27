<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Util;

use \DateTime;
use \DateTimeZone;
use \Icinga\Util\ConfigAwareFactory;
use \Icinga\Exception\ConfigurationError;

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
        if (!array_key_exists('timezone', $config)) {
            throw new ConfigurationError(t('"DateTimeFactory" expects a valid time zone to be set via "setConfig"'));
        }
        self::$timeZone = $config['timezone'];
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
