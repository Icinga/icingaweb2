<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

/**
 * Class Zend_View_Helper_Util
 */
class Zend_View_Helper_Util extends Zend_View_Helper_Abstract
{
    public function util()
    {
        return $this;
    }

    public static function showTimeSince($timestamp)
    {
        if (! $timestamp) {
            return 'unknown';
        }
        $duration = time() - $timestamp;
        if ($duration > 3600 * 24 * 3) {
            if (date('Y') === date('Y', $timestamp)) {
                return date('d.m.', $timestamp);
            }
            return date('m.Y', $timestamp);
        }
        return self::showHourMin($duration);
    }

    public static function showHourMin($sec)
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

    public static function showSeconds($sec)
    {
        // Todo: localization
        if ($sec < 1) {
            return round($sec * 1000) . 'ms';
        }
        if ($sec < 60) {
            return $sec . 's';
        }
        return floor($sec / 60) . 'm ' . ($sec % 60) . 's';
    }

    public static function showTime($timestamp)
    {
        // Todo: localization
        if ($timestamp < 86400) {
            return 'undef';
        }
        if (date('Ymd') === date('Ymd', $timestamp)) {
            return date('H:i:s', $timestamp);
        }
        if (date('Y') === date('Y', $timestamp)) {
            return date('H:i d.m.', $timestamp);
        }
        return date('H:i d.m.Y', $timestamp);
    }
}
