<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command\Instance;

use Icinga\Module\Monitoring\Command\Common\ToggleFeature;

/**
 * Enable/disable host and service notifications on an Icinga instance
 */
class ToggleNotifications extends ToggleFeature
{
    /**
     * The time when notifications should be re-enabled after disabling
     *
     * @var int|null Unix timestamp
     */
    protected $expireTime;

    /**
     * Disable notifications with optional expire time
     *
     * @param   int|null $expireTime The Unix timestamp when notifications should be re-enabled after disabling
     *
     * @return  $this
     */
    public function disable($expireTime = null)
    {
        $this->expireTime = $expireTime !== null ? (int) $expireTime : null;
        return parent::disable();
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\IcingaCommand::getCommandString() For the method documentation.
     */
    public function getCommandString()
    {
        if ($this->enable === true) {
            return 'ENABLE_NOTIFICATIONS';
        }
        if ($this->expireTime !== null) {
            return sprintf(
                '%s;%u;%u',
                'DISABLE_NOTIFICATIONS_EXPIRE_TIME',
                time(),  // Schedule time. According to the Icinga documentation schedule time has no effect currently
                         // and should be set to the current timestamp.
                $this->expireTime
            );
        }
        return 'DISABLE_NOTIFICATIONS';
    }
}
