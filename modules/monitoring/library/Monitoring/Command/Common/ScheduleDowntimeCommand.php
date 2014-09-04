<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command\Common;

use DateTime;

/**
 * Base class for commands scheduling downtimes
 */
abstract class ScheduleDowntimeCommand extends AddCommentCommand
{
    /**
     * Downtime starts at the exact time specified
     *
     * If `Downtime::$flexible' is set to true, the time between `Downtime::$start' and `Downtime::$end' at which a
     * host or service transitions to a problem state determines the time at which the downtime actually starts.
     * The downtime will then last for `Downtime::$duration' seconds.
     *
     * @var DateTime
     */
    protected $start;

    /**
     * Downtime ends at the exact time specified
     *
     * If `Downtime::$flexible' is set to true, the time between `Downtime::$start' and `Downtime::$end' at which a
     * host or service transitions to a problem state determines the time at which the downtime actually starts.
     * The downtime will then last for `Downtime::$duration' seconds.
     *
     * @var DateTime
     */
    protected $end;

    /**
     * Whether it's a flexible downtime
     *
     * @var bool
     */
    protected $flexible = false;

    /**
     * ID of the downtime which triggers this downtime
     *
     * The start of this downtime is triggered by the start of the other scheduled host or service downtime.
     *
     * @var int|null
     */
    protected $triggerId;

    /**
     * The duration in seconds the downtime must last if it's a flexible downtime
     *
     * If `Downtime::$flexible' is set to true, the downtime will last for the duration in seconds specified, even
     * if the host or service recovers before the downtime expires.
     *
     * @var int|null
     */
    protected $duration;

    /**
     * Set the date and time when the downtime should start
     *
     * @param   DateTime $start
     *
     * @return  $this
     */
    public function setStart(DateTime $start)
    {
        $this->start = $start;
        return $this;
    }

    /**
     * Get the date and time when the downtime should start
     *
     * @return DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set the date and time when the downtime should end
     *
     * @param   DateTime $end
     *
     * @return  $this
     */
    public function setEnd(DateTime $end)
    {
        $this->end = $end;
        return $this;
    }

    /**
     * Get the date and time when the downtime should end
     *
     * @return DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set whether is flexible or fixed
     *
     * @param   boolean $flexible
     *
     * @return  $this
     */
    public function setFlexible($flexible = true)
    {
        $this->flexible = (bool) $flexible;
        return $this;
    }

    /**
     * Is the downtime flexible?
     *
     * @return boolean
     */
    public function getFlexible()
    {
        return $this->flexible;
    }

    /**
     * Set the ID of the downtime which triggers this downtime
     *
     * @param   int $triggerId
     *
     * @return  $this
     */
    public function setTriggerId($triggerId)
    {
        $this->triggerId = (int) $triggerId;
        return $this;
    }

    /**
     * Get the ID of the downtime which triggers this downtime
     *
     * @return int|null
     */
    public function getTriggerId()
    {
        return $this->triggerId;
    }

    /**
     * Set the duration in seconds the downtime must last if it's a flexible downtime
     *
     * @param   int $duration
     *
     * @return  $this
     */
    public function setDuration($duration)
    {
        $this->duration = (int) $duration;
        return $this;
    }

    /**
     * Get the duration in seconds the downtime must last if it's a flexible downtime
     *
     * @return int|null
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\IcingaCommand::getCommandString() For the method documentation.
     */
    public function getCommandString()
    {
        return sprintf(
            '%u;%u;%u;%u;%u;%s',
            $this->start->getTimestamp(),
            $this->end->getTimestamp(),
            ! $this->flexible,
            $this->triggerId,
            $this->duration,
            parent::getCommandString()
        );
    }
}
