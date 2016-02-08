<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Date;

/**
 * Date formatting
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
            if (date('Y') === date('Y', $time)) {
                $formatted = date('M j', $time);
            } else {
                $formatted = date('Y-m', $time);
            }
        } else {
            $minutes = floor($diff / 60);
            if ($minutes < 60) {
                $type = static::RELATIVE;
                $formatted = sprintf('%dm %ds', $minutes, $diff % 60);
            } else {
                $hours = floor($minutes / 60);
                if ($hours < 24) {
                    if (date('d') === date('d', $time)) {
                        $type = static::TIME;
                        $formatted = date('H:i', $time);
                    } else {
                        $type = static::DATE;
                        $formatted = date('M j H:i', $time);
                    }
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
        return date('Y-m-d', (float) $date);
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
        return date('Y-m-d H:i:s', (float) $dateTime);
    }

    /**
     * Format a duration
     *
     * @param   int|float $seconds Duration in seconds
     *
     * @return  string
     */
    public static function formatDuration($seconds)
    {
        $minutes = floor((float) $seconds / 60);
        if ($minutes < 60) {
            $formatted = sprintf('%dm %ds', $minutes, $seconds % 60);
        } else {
            $hours = floor($minutes / 60);
            if ($hours < 24) {
                $formatted = sprintf('%dh %dm', $hours, $minutes % 60);
            } else {
                $formatted = sprintf('%dd %dh', floor($hours / 24), $hours % 24);
            }
        }
        return $formatted;
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
        return date('H:i:s', (float) $time);
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
        if ($invert && $type === static::RELATIVE) {
            $until = '-' . $until;
        }
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
                    $until
                );
                break;
            case static::TIME:
                $formatted = sprintf(t('at %s', 'An event will happen at the given time'), $until);
                break;
        }
        return $formatted;
    }
}
