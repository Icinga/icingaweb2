<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

/**
 * Schedule a host downtime
 */
class ScheduleHostDowntimeCommand extends ScheduleServiceDowntimeCommand
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\Object\ObjectCommand::$allowedObjects For the property documentation.
     */
    protected $allowedObjects = array(
        self::TYPE_HOST
    );

    /**
     * Whether to schedule a downtime for all services associated with a particular host
     *
     * @var bool
     */
    protected $forAllServices = false;

    /** @var bool Whether to send the all_services API parameter */
    protected $forAllServicesNative;

    /**
     * Set whether to schedule a downtime for all services associated with a particular host
     *
     * @param   bool $forAllServices
     *
     * @return  $this
     */
    public function setForAllServices($forAllServices = true)
    {
        $this->forAllServices = (bool) $forAllServices;
        return $this;
    }

    /**
     * Get whether to schedule a downtime for all services associated with a particular host
     *
     * @return bool
     */
    public function getForAllServices()
    {
        return $this->forAllServices;
    }

    /**
     * Get whether to send the all_services API parameter
     *
     * @return bool
     */
    public function isForAllServicesNative()
    {
        return $this->forAllServicesNative;
    }

    /**
     * Get whether to send the all_services API parameter
     *
     * @param bool $forAllServicesNative
     *
     * @return $this
     */
    public function setForAllServicesNative($forAllServicesNative = true)
    {
        $this->forAllServicesNative = (bool) $forAllServicesNative;

        return $this;
    }
}
