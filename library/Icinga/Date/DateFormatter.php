<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Date;

use IntlDateFormatter;
use InvalidArgumentException;

/**
 * ICU date formatting
 */
class DateFormatter
{
    /**
     * Internal constant for relative time diffs
     *
     * @var int
     */
    protected static $relative = 0;

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
     * Format time diff as time ago
     *
     * @var int
     */
    const AGO = 8;

    /**
     * Format time diff as time until
     *
     * @var int
     */
    const UNTIL = 16;

    /**
     * Format time diff as time since
     *
     * @var int
     */
    const SINCE = 32;

    /**
     * Format used for the DateFormatter
     *
     * @var int
     */
    protected $format;

    /**
     * Create a date formatter
     *
     * @param int $format
     */
    public function __construct($format)
    {
        $this->format = $format;
    }

    /**
     * Create a date formatter
     *
     * @param   int $format
     *
     * @return  static
     */
    public static function create($format)
    {
        return new static($format);
    }

    /**
     * Format the diff between two date/time values as a string
     *
     * @param   int|float   $time
     * @param   int|float   $now
     *
     * @return  string              The formatted string
     */
    protected function diff($time, $now = null)
    {
        $now = $now === null ? time() : (float) $now;
        $invert = false;
        $diff = $time - $now;
        if ($diff < 0) {
            $diff = abs($diff);
            $invert = true;
        }
        if ($diff > 3600 * 24 * 3) {
            $type = static::DATETIME;
            $fmt = new IntlDateFormatter(null, IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
            $formatted = $fmt->format($time);
        } else {
            $minutes = floor($diff / 60);
            if ($minutes < 60) {
                $type = static::$relative;
                $formatted = sprintf('%dm %ds', $minutes, $diff % 60);
            } else {
                $hours = floor($minutes / 60);
                if ($hours < 24) {
                    $type = static::TIME;
                    $fmt = new IntlDateFormatter(null, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);
                    $formatted = $fmt->format($time);
                } else {
                    $type = static::$relative;
                    $formatted = sprintf('%dd %dh', floor($hours / 24), $hours % 24);
                }
            }
        }
        switch ($this->format) {
            case static::AGO:
                switch ($type) {
                    case static::$relative:
                        $formatted = sprintf(
                            t('%s ago', 'An event that happened the given time interval ago'),
                            $formatted
                        );
                        break;
                    case static::TIME:
                        $formatted = sprintf(t('at %s', 'An event that happened at the given time'), $formatted);
                        break;
                    case static::DATETIME:
                        $formatted = sprintf(
                            t('on %s', 'An event that happened at the given date and time'),
                            $formatted
                        );
                        break;
                }
                break;
            case static::UNTIL:
                switch ($type) {
                    case static::$relative:
                        $formatted = sprintf(
                            t('in %s', 'An event will happen after the given time interval has elapsed'),
                            $invert ? ('-' . $formatted) : $formatted
                        );
                        break;
                    case static::TIME:
                        $formatted = sprintf(t('at %s', 'An event will happen at the given time'), $formatted);
                        break;
                    case static::DATETIME:
                        $formatted = sprintf(t('on %s', 'An event will happen on the given date and time'), $formatted);
                        break;
                }
                break;
            case static::SINCE;
                switch ($type) {
                    case static::$relative:
                        $formatted = sprintf(
                            t('for %s', 'A status is lasting for the given time interval'),
                            $formatted
                        );
                        break;
                    case static::TIME:
                        // Move to next case
                    case static::DATETIME:
                        $formatted = sprintf(
                            t('since %s', 'A status is lasting since the given date and time'),
                            $formatted
                        );
                        break;
                }
                break;
            default:
                break;
        }
        return $formatted;
    }

    /**
     * Format a date/time value as a string
     *
     * @param   int|float   $time   Date/time to format
     * @param   int|float   $now
     *
     * @return  string              The formatted string
     */
    public function format($time, $now = null)
    {
        $time = (float) $time;
        switch ($this->format) {
            case static::TIME:
                $fmt = new IntlDateFormatter(null, IntlDateFormatter::NONE, IntlDateFormatter::SHORT);
                $formatted = $fmt->format($time);
                break;
            case static::DATE;
                $fmt = new IntlDateFormatter(null, IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
                $formatted = $fmt->format($time);
                break;
            case static::DATETIME;
                $fmt = new IntlDateFormatter(null, IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
                $formatted = $fmt->format($time);
                break;
            case static::AGO:
                // Move to next case
            case static::UNTIL:
                // Move to next case
            case static::SINCE:
                $formatted = $this->diff($time, $now);
                break;
            default:
                throw new InvalidArgumentException('Invalid format');
        }
        return $formatted;
    }
}
