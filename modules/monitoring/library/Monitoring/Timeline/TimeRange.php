<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Timeline;

use StdClass;
use Iterator;
use DateTime;
use DateInterval;
use Icinga\Util\Format;

/**
 * A range of time split into a specific interval
 *
 * @see Iterator
 */
class TimeRange implements Iterator
{
    /**
     * The start of this time range
     *
     * @var DateTime
     */
    protected $start;

    /**
     * The end of this time range
     *
     * @var DateTime
     */
    protected $end;

    /**
     * The interval by which this time range is split
     *
     * @var DateInterval
     */
    protected $interval;

    /**
     * The current date in the iteration
     *
     * @var DateTime
     */
    protected $current;

    /**
     * Whether the date iteration is negative
     *
     * @var bool
     */
    protected $negative;

    /**
     * Initialize a new time range
     *
     * @param   DateTime        $start      When the time range should start
     * @param   DateTime        $end        When the time range should end
     * @param   DateInterval    $interval   The interval of the time range
     */
    public function __construct(DateTime $start, DateTime $end, DateInterval $interval)
    {
        $this->interval = $interval;
        $this->start = $start;
        $this->end = $end;
        $this->negative = $this->start > $this->end;
    }

    /**
     * Return when this range of time starts
     *
     * @return  DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Return when this range of time ends
     *
     * @return  DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Return the interval by which this time range is split
     *
     * @return  DateInterval
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Return the appropriate timeframe for the given date and time or null if none could be found
     *
     * @param   DateTime        $dateTime       The date and time for which to search the timeframe
     * @param   bool            $asTimestamp    Whether the start of the timeframe should be returned as timestamp
     * @return  StdClass|int                    An object with a ´start´ and ´end´ property or a timestamp
     */
    public function findTimeframe(DateTime $dateTime, $asTimestamp = false)
    {
        foreach ($this as $timeframeIdentifier => $timeframe) {
            if ($this->negative) {
                if ($dateTime <= $timeframe->start && $dateTime >= $timeframe->end) {
                    return $asTimestamp ? $timeframeIdentifier : $timeframe;
                }
            } elseif ($dateTime >= $timeframe->start && $dateTime <= $timeframe->end) {
                return $asTimestamp ? $timeframeIdentifier : $timeframe;
            }
        }
    }

    /**
     * Return whether the given time is within this range of time
     *
     * @param   int|DateTime    $time   The timestamp or date and time to check
     */
    public function validateTime($time)
    {
        if ($time instanceof DateTime) {
            $dateTime = $time;
        } else {
            $dateTime = new DateTime();
            $dateTime->setTimestamp($time);
        }

        return ($this->negative && ($dateTime <= $this->start && $dateTime >= $this->end)) ||
            (!$this->negative && ($dateTime >= $this->start && $dateTime <= $this->end));
    }

    /**
     * Return the appropriate timeframe for the given timeframe start
     *
     * @param   int|DateTime    $time   The timestamp or date and time for which to return the timeframe
     * @return  StdClass                An object with a ´start´ and ´end´ property
     */
    public function getTimeframe($time)
    {
        if ($time instanceof DateTime) {
            $startTime = clone $time;
        } else {
            $startTime = new DateTime();
            $startTime->setTimestamp($time);
        }

        return $this->buildTimeframe($startTime, $this->applyInterval(clone $startTime, 1));
    }

    /**
     * Apply the current interval to the given date and time
     *
     * @param   DateTime    $dateTime   The date and time to apply the interval to
     * @param   int         $adjustBy   By how much seconds the resulting date and time should be adjusted
     *
     * @return  DateTime
     */
    protected function applyInterval(DateTime $dateTime, $adjustBy)
    {
        if (!$this->interval->y && !$this->interval->m) {
            if ($this->negative) {
                return $dateTime->sub($this->interval)->add(new DateInterval('PT' . $adjustBy . 'S'));
            } else {
                return $dateTime->add($this->interval)->sub(new DateInterval('PT' . $adjustBy . 'S'));
            }
        } elseif ($this->interval->m) {
            for ($i = 0; $i < $this->interval->m; $i++) {
                if ($this->negative) {
                    $dateTime->sub(new DateInterval('PT' . Format::secondsByMonth($dateTime) . 'S'));
                } else {
                    $dateTime->add(new DateInterval('PT' . Format::secondsByMonth($dateTime) . 'S'));
                }
            }
        } elseif ($this->interval->y) {
            for ($i = 0; $i < $this->interval->y; $i++) {
                if ($this->negative) {
                    $dateTime->sub(new DateInterval('PT' . Format::secondsByYear($dateTime) . 'S'));
                } else {
                    $dateTime->add(new DateInterval('PT' . Format::secondsByYear($dateTime) . 'S'));
                }
            }
        }
        $adjustment = new DateInterval('PT' . $adjustBy . 'S');
        return $this->negative ? $dateTime->add($adjustment) : $dateTime->sub($adjustment);
    }

    /**
     * Return an object representation of the given timeframe
     *
     * @param   DateTime    $start  The start of the timeframe
     * @param   DateTime    $end    The end of the timeframe
     * @return  StdClass
     */
    protected function buildTimeframe(DateTime $start, DateTime $end)
    {
        $timeframe = new StdClass();
        $timeframe->start = $start;
        $timeframe->end = $end;
        return $timeframe;
    }

    /**
     * Reset the iterator to its initial state
     */
    public function rewind()
    {
        $this->current = clone $this->start;
    }

    /**
     * Return whether the current iteration step is valid
     *
     * @return  bool
     */
    public function valid()
    {
        if ($this->negative) {
            return $this->current > $this->end;
        } else {
            return $this->current < $this->end;
        }
    }

    /**
     * Return the current value in the iteration
     *
     * @return  StdClass
     */
    public function current()
    {
        return $this->getTimeframe($this->current);
    }

    /**
     * Return a unique identifier for the current value in the iteration
     *
     * @return  int
     */
    public function key()
    {
        return $this->current->getTimestamp();
    }

    /**
     * Advance the iterator position by one
     */
    public function next()
    {
        $this->applyInterval($this->current, 0);
    }
}
