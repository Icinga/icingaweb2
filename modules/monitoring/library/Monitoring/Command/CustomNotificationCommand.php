<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command;

use Icinga\Protocol\Commandpipe\Command;
use Icinga\Protocol\Commandpipe\Comment;

/**
 * Command to send a custom notification
 */
class CustomNotificationCommand extends Command
{
    /**
     * The comment associated with this notification
     *
     * @var Comment
     */
    private $comment;

    /**
     * Whether this notification is forced
     *
     * @var bool
     */
    private $forced;

    /**
     * Whether this notification is also sent to escalation-contacts
     *
     * @var bool
     */
    private $broadcast;

    /**
     * Initialise a new custom notification command object
     *
     * @param   Comment     $comment    The comment for this notification
     * @param   bool        $forced     Whether this notificatin is forced
     * @param   bool        $broadcast  Whether this notification is sent to all contacts
     */
    public function __construct(Comment $comment, $forced = false, $broadcast = false)
    {
        $this->comment = $comment;
        $this->forced = $forced;
        $this->broadcast = $broadcast;
    }

    /**
     * Set the comment for this notification
     *
     * @param   Comment     $comment
     *
     * @return  self
     */
    public function setComment(Comment $comment)
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Set whether this notification is forced
     *
     * @param   bool    $state
     *
     * @return  self
     */
    public function setForced($state)
    {
        $this->forced = (bool) $state;
        return $this;
    }

    /**
     * Set whether this notification is sent to all contacts
     *
     * @param   bool    $state
     *
     * @return  self
     */
    public function setBroadcast($state)
    {
        $this->broadcast = (bool) $state;
        return $this;
    }

    /**
     * Return this command's parameters properly arranged in an array
     *
     * @return  array
     * @see     Command::getArguments()
     */
    public function getArguments()
    {
        $options = 0;
        if ($this->forced) {
            $options |= 2;
        }
        if ($this->broadcast) {
            $options |= 1;
        }
        return array_merge(array($options), $this->comment->getArguments(true));
    }

    /**
     * Return the command as a string with the given host being inserted
     *
     * @param   string  $hostname   The name of the host to insert
     *
     * @return  string              The string representation of the command
     * @see     Command::getHostCommand()
     */
    public function getHostCommand($hostname)
    {
        return 'SEND_CUSTOM_HOST_NOTIFICATION;' . implode(';', array_merge(array($hostname), $this->getArguments()));
    }

    /**
     * Return the command as a string with the given host and service being inserted
     *
     * @param   string  $hostname       The name of the host to insert
     * @param   string  $servicename    The name of the service to insert
     *
     * @return  string                  The string representation of the command
     * @see     Command::getServiceCommand()
     */
    public function getServiceCommand($hostname, $servicename)
    {
        return 'SEND_CUSTOM_SVC_NOTIFICATION;' . implode(
            ';',
            array_merge(
                array($hostname, $servicename),
                $this->getArguments()
            )
        );
    }
}
