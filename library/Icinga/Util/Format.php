<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Util;

use DateTime;
use Icinga\Exception\ProgrammingError;

class Format
{
    const STANDARD_IEC = 0;
    const STANDARD_SI  = 1;
    protected static $instance;

    protected static $bitPrefix = array(
        array('bit', 'Kibit', 'Mibit', 'Gibit', 'Tibit', 'Pibit', 'Eibit', 'Zibit', 'Yibit'),
        array('bit', 'kbit', 'Mbit', 'Gbit', 'Tbit', 'Pbit', 'Ebit', 'Zbit', 'Ybit'),
    );
    protected static $bitBase = array(1024, 1000);

    protected static $bytePrefix = array(
        array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'),
        array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'),
    );
    protected static $byteBase = array(1024, 1000);

    protected static $secondPrefix = array('s', 'ms', 'µs', 'ns', 'ps', 'fs', 'as');
    protected static $secondBase = 1000;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Format;
        }
        return self::$instance;
    }

    public static function bits($value, $standard = self::STANDARD_SI)
    {
        return self::formatForUnits(
            $value,
            self::$bitPrefix[$standard],
            self::$bitBase[$standard]
        );
    }

    public static function bytes($value, $standard = self::STANDARD_IEC)
    {
        return self::formatForUnits(
            $value,
            self::$bytePrefix[$standard],
            self::$byteBase[$standard]
        );
    }

    public static function seconds($value)
    {
        if ($value < 60) {
            return self::formatForUnits($value, self::$secondPrefix, self::$secondBase);
        } elseif ($value < 3600) {
            return sprintf('0.2f m', $value / 60);
        } elseif ($value < 86400) {
            return sprintf('0.2f h', $value / 3600);
        }

        // TODO: Do we need weeks, months and years?
        return sprintf('0.2f d', $value / 86400);
    }

    public static function duration($duration)
    {
        if ($duration === null || $duration === false) {
            return '-';
        }
        return self::showHourMin($duration);
    }

    protected static function showHourMin($sec, $includePrefix = false)
    {
        $min = floor($sec / 60);
        if ($min < 60) {
            return ($includePrefix ? t('for') . ' ' : '') . $min . 'm ' . ($sec % 60) . 's';
        }
        $hour = floor($min / 60);
        if ($hour < 24) {
            return ($includePrefix ? t('since') . ' ' : '') . date('H:i', time() - $sec);
        }
        return ($includePrefix ? t('for') . ' ' : '') . floor($hour / 24) . 'd ' . ($hour % 24) . 'h';
    }

    protected static function smartTimeDiff($diff, $timestamp, $includePrefix = false)
    {
        if ($timestamp === null || $timestamp === false) {
            return '-';
        }
        if (! preg_match('~^\d+$~', $timestamp)) {
            throw new ProgrammingError(sprintf('"%s" is not a number', $timestamp));
        }
        $prefix = '';
        if ($diff < 0) {
            $prefix = '-';
        }
        if (abs($diff) > 3600 * 24 * 3) {
            if (date('Y') === date('Y', $timestamp)) {
                return ($includePrefix ? t('since') . ' ' : '') . date('d.m.', $timestamp);
            }
            return ($includePrefix ? t('since') . ' ' : '') . date('m.Y', $timestamp);
        }
        return $prefix . self::showHourMin(abs($diff), $includePrefix);
    }

    public static function timeSince($timestamp)
    {
        return self::smartTimeDiff(time() - $timestamp, $timestamp);
    }

    public static function prefixedTimeSince($timestamp, $ucfirst = false)
    {
        $result = self::smartTimeDiff(time() - $timestamp, $timestamp, true);
        if ($ucfirst) {
            $result = ucfirst($result);
        }
        return $result;
    }

    public static function timeUntil($timestamp)
    {
        return self::smartTimeDiff($timestamp - time(), $timestamp);
    }

    public static function prefixedTimeUntil($timestamp, $ucfirst)
    {
        $result = self::smartTimeDiff($timestamp - time(), $timestamp, true);
        if ($ucfirst) {
            $result = ucfirst($result);
        }
        return $result;
    }

    protected static function formatForUnits($value, & $units, $base)
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
     * @param   DateTime|int    $dateTimeOrTimestamp    The date and time to use
     *
     * @return  int
     */
    public static function secondsByMonth($dateTimeOrTimestamp)
    {
        if (!($dt = $dateTimeOrTimestamp) instanceof DateTime) {
            $dt = new DateTime();
            $dt->setTimestamp($dateTimeOrTimestamp);
        }

        return (int) $dt->format('t') * 24 * 3600;
    }

    /**
     * Return the amount of seconds based on the given year
     *
     * @param   DateTime|int    $dateTimeOrTimestamp    The date and time to use
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
     * @param   DateTime|int    $dateTimeOrTimestamp    The date and time to use
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
}
