<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Commandpipe;

/**
 * Custom notification about hosts or services sent to Icinga's command pipe
 */
class CustomNotification
{
    /**
     * Notification comment
     *
     * @var string
     */
    private $comment;

    /**
     *  Notification author
     *
     *  @var string
     */
    private $author;

    /**
     * Whether to force the notification to be sent out, regardless of the time restrictions, whether or not
     * notifications are enabled, etc.
     *
     * @var bool
     */
    private $forced;

    /**
     * Whether the notification is sent out to all normal (non-escalated) and escalated contacts
     *
     * @var bool
     */
    private $broadcast;

    /**
     * 1 = Broadcast (send notification to all normal and all escalated contacts for the host)
     */
    const NOTIFY_BROADCAST = 1;

    /**
     * 2 = Forced (notification is sent out regardless of current time, whether or not notifications are enabled, etc.)
     */
    const NOTIFY_FORCED = 2;

    /**
     * Constructor
     *
     * @param string    $author     Notification author
     * @param string    $comment    Notification comment
     * @param bool      $forced     Whether to force the notification to be sent out, regardless of the time
     *                              restrictions, whether or not notifications are enabled, etc.
     * @param bool      $broadcast  Whether the notification is sent out to all normal (non-escalated) and escalated
     *                              contacts
     */
    public function __construct($author, $comment, $forced = false, $broadcast = false)
    {
        $this->author = $author;
        $this->comment = $comment;
        $this->forced = $forced;
        $this->broadcast = $broadcast;
    }

    /**
     * Get Custom Notification command format string according to if its sent to a host or a service
     *
     * @param   string $type Identifier for either host or service
     *
     * @return  string
     *
     * @throws  InvalidCommandException When the given type is unknown
     * @see     \Icinga\Protocol\Commandpipe\CommandPipe::TYPE_HOST
     * @see     \Icinga\Protocol\Commandpipe\CommandPipe::TYPE_SERVICE
     */
    public function getFormatString($type)
    {
        switch ($type) {
            case CommandPipe::TYPE_HOST:
                $format = '%s';
                break;
            case CommandPipe::TYPE_SERVICE:
                $format = '%s;%s';
                break;
            default:
                throw new InvalidCommandException('Custom Notifications can only apply on hosts and services');
        }

        $options = 0;
        if ($this->forced) {
            $options |= self::NOTIFY_FORCED;
        }
        if ($this->broadcast) {
            $options |= self::NOTIFY_BROADCAST;
        }

        // Build the command
        $command = 'SEND_CUSTOM_' . $type . '_NOTIFICATION;'
            . $format . ';'
            . $options . ';'
            . $this->author . ';'
            . $this->comment;
        return $command;
    }
}
