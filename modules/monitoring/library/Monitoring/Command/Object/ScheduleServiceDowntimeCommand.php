<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

/**
 * Schedule a service downtime
 */
class ScheduleServiceDowntimeCommand extends AddCommentCommand
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\Object\ObjectCommand::$allowedObjects For the property documentation.
     */
    protected $allowedObjects = array(
        self::TYPE_SERVICE
    );

    /**
     * Downtime starts at the exact time specified
     *
     * If `Downtime::$fixed' is set to false, the time between `Downtime::$start' and `Downtime::$end' at which a
     * host or service transitions to a problem state determines the time at which the downtime actually starts.
     * The downtime will then last for `Downtime::$duration' seconds.
     *
     * @var int Unix timestamp
     */
    protected $start;

    /**
     * Downtime ends at the exact time specified
     *
     * If `Downtime::$fixed' is set to false, the time between `Downtime::$start' and `Downtime::$end' at which a
     * host or service transitions to a problem state determines the time at which the downtime actually starts.
     * The downtime will then last for `Downtime::$duration' seconds.
     *
     * @var int Unix timestamp
     */
    protected $end;

    /**
     * Whether it's a fixed or flexible downtime
     *
     * @var bool
     */
    protected $fixed = true;

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
     * If `Downtime::$fixed' is set to false, the downtime will last for the duration in seconds specified, even
     * if the host or service recovers before the downtime expires.
     *
     * @var int|null
     */
    protected $duration;

    /**
     * Set the time when the downtime should start
     *
     * @param   int $start Unix timestamp
     *
     * @return  $this
     */
    public function setStart($start)
    {
        $this->start = (int) $start;
        return $this;
    }

    /**
     * Get the time when the downtime should start
     *
     * @return int Unix timestamp
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set the time when the downtime should end
     *
     * @param   int $end Unix timestamp
     *
     * @return  $this
     */
    public function setEnd($end)
    {
        $this->end = (int) $end;
        return $this;
    }

    /**
     * Get the time when the downtime should end
     *
     * @return int Unix timestamp
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set whether it's a fixed or flexible downtime
     *
     * @param   boolean $fixed
     *
     * @return  $this
     */
    public function setFixed($fixed = true)
    {
        $this->fixed = (bool) $fixed;
        return $this;
    }

    /**
     * Is the downtime fixed?
     *
     * @return boolean
     */
    public function getFixed()
    {
        return $this->fixed;
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
     * @see \Icinga\Module\Monitoring\Command\Object\IcingaCommand::getName() For the method documentation.
     */
    public function getName()
    {
        return 'ScheduleDowntime';
    }
}
