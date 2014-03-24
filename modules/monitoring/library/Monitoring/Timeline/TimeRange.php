<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Timeline;

use \StdClass;
use \Iterator;
use \DateTime;
use \DateInterval;

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

        $endTime = clone $startTime;

        if ($this->negative) {
            $endTime->sub($this->interval);
            $endTime->add(new DateInterval('PT1S'));
        } else {
            $endTime->add($this->interval);
            $endTime->sub(new DateInterval('PT1S'));
        }

        return $this->buildTimeframe($startTime, $endTime);
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
        if ($this->negative) {
            $this->current->sub($this->interval);
        } else {
            $this->current->add($this->interval);
        }
    }
}
