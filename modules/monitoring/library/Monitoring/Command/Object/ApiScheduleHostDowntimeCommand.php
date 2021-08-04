<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

/**
 * Schedule host downtime command for API command transport and Icinga >= 2.11.0 that
 * sends all_services and child_options in a single request
 */
class ApiScheduleHostDowntimeCommand extends ScheduleHostDowntimeCommand
{
    /** @var int Whether no, triggered, or non-triggered child downtimes should be scheduled */
    protected $childOptions;

    protected $forAllServicesNative = true;

    /**
     * Get child options, i.e. whether no, triggered, or non-triggered child downtimes should be scheduled
     *
     * @return int
     */
    public function getChildOptions()
    {
        return $this->childOptions;
    }

    /**
     * Set child options, i.e. whether no, triggered, or non-triggered child downtimes should be scheduled
     *
     * @param int $childOptions
     *
     * @return $this
     */
    public function setChildOptions($childOptions)
    {
        $this->childOptions = $childOptions;

        return $this;
    }
}
