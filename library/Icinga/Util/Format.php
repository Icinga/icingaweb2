<?php

namespace Icinga\Util;

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

    public static function duration($duration)
    {
        if (! $duration) {
            return '-';
        }
        return self::showHourMin($duration);
    }

    protected static function showHourMin($sec)
    {
        $min = floor($sec / 60);
        if ($min < 60) {
            return $min . 'm ' . ($sec % 60) . 's';
        }
        $hour = floor($min / 60);
        if ($hour < 24) {
            return date('H:i', time() - $sec);
        }
        return floor($hour / 24) . 'd ' . ($hour % 24) . 'h';
    }

    public static function timeSince($timestamp)
    {
        if (! $timestamp) {
            return '-';
        }
        $duration = time() - $timestamp;
        $prefix = '';
        if ($duration < 0) {
            $prefix = '-';
            $duration *= -1;
        }
        if ($duration > 3600 * 24 * 3) {
            if (date('Y') === date('Y', $timestamp)) {
                return date('d.m.', $timestamp);
            }
            return date('m.Y', $timestamp);
        }
        return $prefix . self::showHourMin($duration);
    }

    public static function timeUntil($timestamp)
    {
        return self::duration($timestamp - time());
    }

    protected static function formatForUnits($value, & $units, $base)
    {
        $sign = '';
        if ($value < 0) {
            $value = abs($value);
            $sign = '-';
        }
        $pow = floor(log($value, $base));
        $result =  $value / pow($base, $pow);

        // 1034.23 looks better than 1.03, but 2.03 is fine:
        if ($pow > 0 && $result < 2) {
            $pow--;
            $result =  $value / pow($base, $pow);
        }
        return sprintf(
            '%s%0.2f %s',
            $sign,
            $result,
            $units[$pow]
        );
    }
}
