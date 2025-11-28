<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Instance;

use Icinga\Module\Monitoring\Command\IcingaCommand;

/**
 * Disable host and service notifications w/ expire time on an Icinga instance
 */
class DisableNotificationsExpireCommand extends IcingaCommand
{
    /**
     * The time when notifications should be re-enabled after disabling
     *
     * @var int|null Unix timestamp
     */
    protected $expireTime;

    /**
     * Set time when notifications should be re-enabled after disabling
     *
     * @param   $expireTime int Unix timestamp
     *
     * @return  $this
     */
    public function setExpireTime($expireTime)
    {
        $this->expireTime = (int) $expireTime;
        return $this;
    }

    /**
     * Get the date and time when notifications should be re-enabled after disabling
     *
     * @return int|null Unix timestamp
     */
    public function getExpireTime()
    {
        return $this->expireTime;
    }
}
