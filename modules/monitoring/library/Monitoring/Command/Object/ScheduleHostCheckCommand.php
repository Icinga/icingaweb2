<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

/**
 * Schedule a host check
 */
class ScheduleHostCheckCommand extends ScheduleServiceCheckCommand
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\Object\ObjectCommand::$allowedObjects For the property documentation.
     */
    protected $allowedObjects = array(
        self::TYPE_HOST
    );

    /**
     * Whether to schedule a check of all services associated with a particular host
     *
     * @var bool
     */
    protected $ofAllServices = false;

    /**
     * Set whether to schedule a check of all services associated with a particular host
     *
     * @param   bool $ofAllServices
     *
     * @return  $this
     */
    public function setOfAllServices($ofAllServices = true)
    {
        $this->ofAllServices = (bool) $ofAllServices;
        return $this;
    }

    /**
     * Get whether to schedule a check of all services associated with a particular host
     *
     * @return bool
     */
    public function getOfAllServices()
    {
        return $this->ofAllServices;
    }
}
