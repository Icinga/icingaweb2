<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

/**
 * Schedule and propagate host downtime
 */
class PropagateHostDowntimeCommand extends ScheduleServiceDowntimeCommand
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\Object\ObjectCommand::$allowedObjects For the property documentation.
     */
    protected $allowedObjects = array(
        self::TYPE_HOST
    );

    /**
     * Whether the downtime for child hosts are all set to be triggered by this' host downtime
     *
     * @var bool
     */
    protected $triggered = false;

    /**
     * Set whether the downtime for child hosts are all set to be triggered by this' host downtime
     *
     * @param   bool $triggered
     *
     * @return  $this
     */
    public function setTriggered($triggered = true)
    {
        $this->triggered = (bool) $triggered;
        return $this;
    }

    /**
     * Get whether the downtime for child hosts are all set to be triggered by this' host downtime
     *
     * @return bool
     */
    public function getTriggered()
    {
        return $this->triggered;
    }
}
