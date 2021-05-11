<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

use DateTime;

class Format
{
    protected static $instance;

    protected static $bitPrefix = ['b', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb'];

    protected static $bytePrefix = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

    protected static $timeWattPrefix = [
        'WattHours'   => ['Wh', 'kWh', 'MWh', 'GWh', 'TWh', 'PWh', 'EWh', 'ZWh', 'YWh'],
        'WattMinutes' => ['Wm', 'kWm', 'MWm', 'GWm', 'TWm', 'PWm', 'EWm', 'ZWm', 'YWm'],
        'WattSeconds' => ['Ws', 'kWs', 'MWs', 'GWs', 'TWs', 'PWs', 'EWs', 'ZWs', 'YWs'],
    ];

    protected static $wattPrefix = ['W', 'kW', 'MW', 'GW', 'TW', 'PW', 'EW', 'ZW', 'YW'];

    protected static $amperePrefix = ['A', 'kA', 'MA', 'GA', 'TA', 'PA', 'EA', 'ZA', 'YA'];

    protected static $timeAmperePrefix = [
        'AmpHours'   => ['Ah', 'kAh', 'MAh', 'GAh', 'TAh', 'PAh', 'EAh', 'ZAh', 'YAh'],
        'AmpMinutes' => ['Am', 'kAm', 'MAm', 'GAm', 'TAm', 'PAm', 'EAm', 'ZAm', 'YAm'],
        'AmpSeconds' => ['As', 'kAs', 'MAs', 'GAs', 'TAs', 'PAs', 'EAs', 'ZAs', 'YAs'],
    ];

    protected static $voltPrefix = ['V', 'kV', 'MV', 'GV', 'TV', 'PV', 'EV', 'ZV', 'YV'];

    protected static $ohmPrefix = ['O', 'kO', 'MO', 'GO', 'TO', 'PO', 'EO', 'ZO', 'YO'];

    protected static $gramPrefix = ['g', 'kg', 't'];

    protected static $literPrefix = ['l', 'hl'];
    protected static $literBase = 100;

    protected static $secondPrefix = array('s');
    protected static $baseFactor = 1000;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Format;
        }
        return self::$instance;
    }

    public static function bits($value)
    {
        return self::formatForUnits($value, self::$bitPrefix, self::$baseFactor);
    }

    public static function bytes($value)
    {
        return self::formatForUnits($value, self::$bytePrefix, self::$baseFactor);
    }

    public static function timeWatts($value, $wattType)
    {
        return self::formatForUnits($value, self::$timeWattPrefix[$wattType], self::$baseFactor);
    }

    public static function watts($value)
    {
        return self::formatForUnits($value, self::$wattPrefix, self::$baseFactor);
    }

    public static function amperes($value)
    {
        return self::formatForUnits($value, self::$amperePrefix, self::$baseFactor);
    }

    public static function timeAmperes($value, $ampereType)
    {
        return self::formatForUnits($value, self::$timeAmperePrefix[$ampereType], self::$baseFactor);
    }

    public static function volts($value)
    {
        return self::formatForUnits($value, self::$voltPrefix, self::$baseFactor);
    }

    public static function ohms($value)
    {
        return self::formatForUnits($value, self::$ohmPrefix, self::$baseFactor);
    }

    public static function grams($value)
    {
        return self::formatForUnits($value, self::$gramPrefix, self::$baseFactor);
    }

    public static function liters($value)
    {
        return self::formatForUnits($value, self::$literPrefix, self::$literBase);
    }

    public static function seconds($value)
    {
        $absValue = abs($value);

        if ($absValue < 60) {
            return self::formatForUnits($value, self::$secondPrefix, self::$baseFactor);
        } elseif ($absValue < 3600) {
            return sprintf('%0.2f m', $value / 60);
        } elseif ($absValue < 86400) {
            return sprintf('%0.2f h', $value / 3600);
        }

        // TODO: Do we need weeks, months and years?
        return sprintf('%0.2f d', $value / 86400);
    }

    protected static function formatForUnits($value, &$units, $base)
    {
        $sign = '';
        if ($value < 0) {
            $value = abs($value);
            $sign = '-';
        }

        if ($value == 0) {
            $pow = $result = 0;
        } else {
            $pow = floor(log($value, $base));
            $result = $value / pow($base, $pow);
        }

        // 1034.23 looks better than 1.03, but 2.03 is fine:
        if ($pow > 0 && $result < 2) {
            $result = $value / pow($base, --$pow);
        }

        return sprintf(
            '%s%0.2f %s',
            $sign,
            $result,
            $units[abs($pow)]
        );
    }

    /**
     * Return the amount of seconds based on the given month
     *
     * @param DateTime|int $dateTimeOrTimestamp The date and time to use
     *
     * @return  int
     */
    public static function secondsByMonth($dateTimeOrTimestamp)
    {
        if (!($dt = $dateTimeOrTimestamp) instanceof DateTime) {
            $dt = new DateTime();
            $dt->setTimestamp($dateTimeOrTimestamp);
        }

        return (int)$dt->format('t') * 24 * 3600;
    }

    /**
     * Return the amount of seconds based on the given year
     *
     * @param DateTime|int $dateTimeOrTimestamp The date and time to use
     *
     * @return  int
     */
    public static function secondsByYear($dateTimeOrTimestamp)
    {
        return (self::isLeapYear($dateTimeOrTimestamp) ? 366 : 365) * 24 * 3600;
    }

    /**
     * Return whether the given year is a leap year
     *
     * @param DateTime|int $dateTimeOrTimestamp The date and time to use
     *
     * @return  bool
     */
    public static function isLeapYear($dateTimeOrTimestamp)
    {
        if (!($dt = $dateTimeOrTimestamp) instanceof DateTime) {
            $dt = new DateTime();
            $dt->setTimestamp($dateTimeOrTimestamp);
        }

        return $dt->format('L') == 1;
    }

    public static function getBitPrefix()
    {
        return self::$bitPrefix;
    }

    public static function getBytePrefix()
    {
        return self::$bytePrefix;
    }

    public static function getTimeWattPrefix()
    {
        return self::$timeWattPrefix;
    }

    public static function getWattPrefix()
    {
        return self::$wattPrefix;
    }

    public static function getAmperePrefix()
    {
        return self::$amperePrefix;
    }

    public static function getTimeAmpPrefix()
    {
        return self::$timeAmperePrefix;
    }

    public static function getVoltPrefix()
    {
        return self::$voltPrefix;
    }

    public static function getOhmPrefix()
    {
        return self::$ohmPrefix;
    }

    public static function getGramPrefix()
    {
        return self::$gramPrefix;
    }

    public static function getLiterPrefix()
    {
        return self::$literPrefix;
    }

    /**
     * Unpack shorthand bytes PHP directives to bytes
     *
     * @param   string  $subject
     *
     * @return  int
     */
    public static function unpackShorthandBytes($subject)
    {
        $base = (int) $subject;

        if ($base <= -1) {
            return INF;
        }

        switch (strtoupper($subject[strlen($subject) - 1])) {
            case 'K':
                $multiplier = 1024;
                break;
            case 'M':
                $multiplier = 1024 ** 2;
                break;
            case 'G':
                $multiplier = 1024 ** 3;
                break;
            default:
                $multiplier = 1;
                break;
        }

        return $base * $multiplier;
    }
}
