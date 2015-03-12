<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
     * Whether a notification is forced to send
     *
     * Forced notifications are send regardless of time and if notifications
     * are enabled.
     *
     * @var bool
     */
    protected $forced;

    /**
     * Broadcast the notification
     *
     * If broadcast is true, the notification is send to all normal and
     * escalated contacts for the object
     *
     * @var bool
     */
    protected $broadcast;

    /**
     * Get notification force flag
     *
     * @return bool
     */
    public function getForced()
    {
        return $this->forced;
    }

    /**
     * Set whether notification should be forced
     *
     * @param bool $forced
     */
    public function setForced($forced = true)
    {
        $this->forced = $forced;
    }

    /**
     * Get notification broadcast flag
     *
     * @return bool
     */
    public function getBroadcast()
    {
        return $this->broadcast;
    }

    /**
     * Set notification to broadcast
     *
     * @param bool $broadcast
     */
    public function setBroadcast($broadcast = true)
    {
        $this->broadcast = $broadcast;
    }
}
