<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
     * If the command affects a service downtime
     *
     * @var boolean
     */
    protected $isService = false;

    /**
     * Set if this command affects a service
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

    /**
     * Return whether the command affects a service
     *
     * @return bool
     */
    public function getIsService()
    {
        return $this->isService;
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
     * Get the ID of the downtime that is to be deleted
     *
     * @return int
     */
    public function getDowntimeId()
    {
        return $this->downtimeId;
    }
}
