<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Date;

use IntlDateFormatter;

/**
 * ICU date formatting
 */
class DateFormatter
{
    /**
     * Format relative
     *
     * @var int
     */
    const RELATIVE = 0;

    /**
     * Format time
     *
     * @var int
     */
    const TIME = 1;

    /**
     * Format date
     *
     * @var int
     */
    const DATE = 2;

    /**
     * Format date and time
     *
     * @var int
     */
    const DATETIME = 4;

    /**
     * Get the diff between the given time and the current time
     *
     * @param   int|float $time
     *
     * @return  array
     */
    protected static function diff($time)
    {
        $invert = false;
        $now = time();
        $time = (float) $time;
        $diff = $time - $now;
        if ($diff < 0) {
            $diff = abs($diff);
            $invert = true;
        }
        if ($diff > 3600 * 24 * 3) {
            $type = static::DATE;
            $fmt = new IntlDateFormatter(null, IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
            $formatted = $fmt->format($time);
        } else {
            $minutes = floor($diff / 60);
            if ($minutes < 60) {
                $type = static::RELATIVE;
                $formatted = sprintf('%dm %ds', $minutes, $diff % 60);
            } else {
                $hours = floor($minutes / 60);
                if ($hours < 24) {
                    $type = static::TIME;
                    $fmt = new IntlDateFormatter(null, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);
                    $formatted = $fmt->format($time);
                } else {
                    $type = static::RELATIVE;
                    $formatted = sprintf('%dd %dh', floor($hours / 24), $hours % 24);
                }
            }
        }
        return array($type, $formatted, $invert);
    }

    /**
     * Format date
     *
     * @param   int|float $date
     *
     * @return  string
     */
    public static function formatDate($date)
    {
        $fmt = new IntlDateFormatter(null, IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
        return $fmt->format((float) $date);
    }

    /**
     * Format date and time
     *
     * @param   int|float $dateTime
     *
     * @return  string
     */
    public static function formatDateTime($dateTime)
    {
        $fmt = new IntlDateFormatter(null, IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
        return $fmt->format((float) $dateTime);
    }

    /**
     * Format time
     *
     * @param   int|float $time
     *
     * @return  string
     */
    public static function formatTime($time)
    {
        $fmt = new IntlDateFormatter(null, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);
        return $fmt->format((float) $time);
    }

    /**
     * Format time as time ago
     *
     * @param   int|float   $time
     * @param   bool        $timeOnly
     *
     * @return  string
     */
    public static function timeAgo($time, $timeOnly = false)
    {
        list($type, $ago, $invert) = static::diff($time);
        if ($timeOnly) {
            return $ago;
        }
        switch ($type) {
            case static::DATE:
                // Move to next case
            case static::DATETIME:
                $formatted = sprintf(
                    t('on %s', 'An event happened on the given date or date and time'),
                    $ago
                );
                break;
            case static::RELATIVE:
                $formatted = sprintf(
                    t('%s ago', 'An event that happened the given time interval ago'),
                    $ago
                );
                break;
            case static::TIME:
                $formatted = sprintf(t('at %s', 'An event happened at the given time'), $ago);
                break;
        }
        return $formatted;
    }

    /**
     * Format time as time since
     *
     * @param   int|float   $time
     * @param   bool        $timeOnly
     *
     * @return  string
     */
    public static function timeSince($time, $timeOnly = false)
    {
        list($type, $since, $invert) = static::diff($time);
        if ($timeOnly) {
            return $since;
        }
        switch ($type) {
            case static::RELATIVE:
                $formatted = sprintf(
                    t('for %s', 'A status is lasting for the given time interval'),
                    $since
                );
                break;
            case static::DATE:
                // Move to next case
            case static::DATETIME:
                // Move to next case
            case static::TIME:
                $formatted = sprintf(
                    t('since %s', 'A status is lasting since the given time, date or date and time'),
                    $since
                );
                break;
        }
        return $formatted;
    }

    /**
     * Format time as time until
     *
     * @param   int|float   $time
     * @param   bool        $timeOnly
     *
     * @return  string
     */
    public static function timeUntil($time, $timeOnly = false)
    {
        list($type, $until, $invert) = static::diff($time);
        if ($timeOnly) {
            return $until;
        }
        switch ($type) {
            case static::DATE:
                // Move to next case
            case static::DATETIME:
                $formatted = sprintf(
                    t('on %s', 'An event will happen on the given date or date and time'),
                    $until
                );
                break;
            case static::RELATIVE:
                $formatted = sprintf(
                    t('in %s', 'An event will happen after the given time interval has elapsed'),
                    $invert ? ('-' . $until) : $until
                );
                break;
            case static::TIME:
                $formatted = sprintf(t('at %s', 'An event will happen at the given time'), $until);
                break;
        }
        return $formatted;
    }
}
