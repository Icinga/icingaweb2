<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command\Service;

use Icinga\Module\Monitoring\Command\Common\ScheduleDowntimeCommand;
use Icinga\Module\Monitoring\Object\Service;

/**
 * Schedule a service downtime on an Icinga instance
 */
class ScheduleServiceDowntimeCommand extends ScheduleDowntimeCommand
{
    /**
     * Service to set in downtime
     *
     * @var Service
     */
    protected $service;

    /**
     * Set the service to set in downtime
     *
     * @param   Service $service
     *
     * @return  $this
     */
    public function setService(Service $service)
    {
        $this->service = $service;
        return $this;
    }

    /**
     * Get the service to set in downtime
     *
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\IcingaCommand::getCommandString() For the method documentation.
     */
    public function getCommandString()
    {
        return sprintf(
            '%s;%s;%s;%s',
            'SCHEDULE_SVC_DOWNTIME',
            $this->service->getHostName(),
            $this->service->getName(),
            parent::getCommandString()
        );
    }
}
