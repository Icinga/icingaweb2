<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

use Icinga\Module\Monitoring\Command\IcingaCommand;

/**
 * Delete a host or service downtime
 */
class DeleteDowntimeCommand extends IcingaCommand
{
    /**
     * ID of the downtime that is to be deleted
     *
     * @var int
     */
    protected $downtimeId;

    /**
     * Name of the downtime (Icinga 2.4+)
     *
     * Required for removing the downtime via Icinga 2's API.
     *
     * @var string
     */
    protected $downtimeName;

    /**
     * Whether the command affects a service downtime
     *
     * @var boolean
     */
    protected $isService = false;

    /**
     * Get the ID of the downtime that is to be deleted
     *
     * @return int
     */
    public function getDowntimeId()
    {
        return $this->downtimeId;
    }

    /**
     * Set the ID of the downtime that is to be deleted
     *
     * @param   int $downtimeId
     *
     * @return  $this
     */
    public function setDowntimeId($downtimeId)
    {
        $this->downtimeId = (int) $downtimeId;
        return $this;
    }

    /**
     * Get the name of the downtime (Icinga 2.4+)
     *
     * Required for removing the downtime via Icinga 2's API.
     *
     * @return string
     */
    public function getDowntimeName()
    {
        return $this->downtimeName;
    }

    /**
     * Set the name of the downtime (Icinga 2.4+)
     *
     * Required for removing the downtime via Icinga 2's API.
     *
     * @param   string  $downtimeName
     *
     * @return  $this
     */
    public function setDowntimeName($downtimeName)
    {
        $this->downtimeName = $downtimeName;
        return $this;
    }

    /**
     * Get whether the command affects a service
     *
     * @return bool
     */
    public function getIsService()
    {
        return $this->isService;
    }

    /**
     * Set whether the command affects a service
     *
     * @param   bool $isService
     *
     * @return  $this
     */
    public function setIsService($isService = true)
    {
        $this->isService = (bool) $isService;
        return $this;
    }
}
