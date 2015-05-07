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
     * Downtime for a host
     */
    const DOWNTIME_TYPE_HOST = 'host';

    /**
     * Downtime for a service
     */
    const DOWNTIME_TYPE_SERVICE = 'service';

    /**
     * ID of the downtime that is to be deleted
     *
     * @var int
     */
    protected $downtimeId;
    
    /**
     *
     * @var type
     */
    protected $downtimeType = self::DOWNTIME_TYPE_HOST;
    
    /**
     * Set the downtime type, either host or service
     *
     * @param string $type  the downtime type
     */
    public function setDowntimeType($type)
    {
        $this->downtimeType = $type;
    }
    
    /**
     * 
     * @return type
     */
    public function getDowntimeType()
    {
        return $this->downtimeType;
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
