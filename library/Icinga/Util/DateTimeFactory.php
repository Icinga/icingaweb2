<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Util;

use \DateTime;
use \DateTimeZone;
use Icinga\Util\ConfigAwareFactory;
use Icinga\Exception\ConfigurationError;

class DateTimeFactory implements ConfigAwareFactory
{
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
     * Return new DateTime object using the set time zone
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
