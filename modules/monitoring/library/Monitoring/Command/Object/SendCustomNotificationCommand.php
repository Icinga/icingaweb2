<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

/**
 * Send custom notifications for a host or service
 */
class SendCustomNotificationCommand extends WithCommentCommand
{
    /**
     * {@inheritdoc}
     */
    protected $allowedObjects = array(
        self::TYPE_HOST,
        self::TYPE_SERVICE
    );

    /**
     * Whether the notification is forced
     *
     * Forced notifications are sent out regardless of time restrictions and whether or not notifications are enabled.
     *
     * @var bool
     */
    protected $forced;

    /**
     * Whether to broadcast the notification
     *
     * Broadcast notifications are sent out to all normal and escalated contacts.
     *
     * @var bool
     */
    protected $broadcast;

    /**
     * Get whether to force the notification
     *
     * @return bool
     */
    public function getForced()
    {
        return $this->forced;
    }

    /**
     * Set whether to force the notification
     *
     * @param   bool $forced
     *
     * @return  $this
     */
    public function setForced($forced = true)
    {
        $this->forced = $forced;
        return $this;
    }

    /**
     * Get whether to broadcast the notification
     *
     * @return bool
     */
    public function getBroadcast()
    {
        return $this->broadcast;
    }

    /**
     * Set whether to broadcast the notification
     *
     * @param   bool $broadcast
     *
     * @return  $this
     */
    public function setBroadcast($broadcast = true)
    {
        $this->broadcast = $broadcast;
        return $this;
    }
}
