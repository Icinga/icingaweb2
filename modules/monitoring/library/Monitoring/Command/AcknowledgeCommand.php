<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command;

use Icinga\Protocol\Commandpipe\Command;
use Icinga\Protocol\Commandpipe\Comment;

/**
 * Command for acknowledging an object
 */
class AcknowledgeCommand extends Command
{
    /**
     * When this acknowledgement should expire
     *
     * @var int    The time as UNIX timestamp or -1 if it shouldn't expire
     */
    private $expireTime = -1;

    /**
     * The comment associated to this acknowledgment
     *
     * @var Comment
     */
    private $comment;

    /**
     * Whether to set the notify flag of this acknowledgment
     *
     * @var bool
     */
    private $notify;

    /**
     * Whether this acknowledgement is of type sticky
     *
     * @var bool
     */
    private $sticky;

    /**
     * Initialise a new acknowledgement command object
     *
     * @param   Comment $comment    The comment to use for this acknowledgement
     * @param   int     $expire     The expire time or -1 of not expiring
     * @param   bool    $notify     Whether to set the notify flag
     * @param   bool    $sticky     Whether to set the sticky flag
     */
    public function __construct(Comment $comment, $expire = -1, $notify = false, $sticky = false)
    {
        $this->expireTime = $expire;
        $this->comment = $comment;
        $this->notify = $notify;
        $this->sticky = $sticky;
    }

    /**
     * Set the time when this acknowledgement should expire
     *
     * @param   int     $expireTime     The time as UNIX timestamp or -1 if it shouldn't expire
     *
     * @return  self
     */
    public function setExpire($expireTime)
    {
        $this->expireTime = (int) $expireTime;
        return $this;
    }

    /**
     * Set the comment for this acknowledgement
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
     * Set whether the notify flag of this acknowledgment should be set
     *
     * @param   bool    $state
     *
     * @return  self
     */
    public function setNotify($state)
    {
        $this->notify = (bool) $state;
        return $this;
    }

    /**
     * Set whether this acknowledgement is of type sticky
     *
     * @param   bool    $state
     *
     * @return  self
     */
    public function setSticky($state)
    {
        $this->sticky = (bool) $state;
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
        $parameters = array_merge(
            array(
                $this->sticky ? '2' : '0',
                $this->notify ? '1' : '0'
            ),
            $this->comment->getArguments()
        );

        if ($this->expireTime > -1) {
            array_splice($parameters, 3, 0, array($this->expireTime));
        }

        return $parameters;
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
        $parameters = $this->getArguments();
        return sprintf('ACKNOWLEDGE_HOST_PROBLEM%s;', $this->expireTime > -1 ? '_EXPIRE' : '')
               . implode(';', array_merge(array($hostname), $parameters));
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
        $parameters = $this->getArguments();
        return sprintf('ACKNOWLEDGE_SVC_PROBLEM%s;', $this->expireTime > -1 ? '_EXPIRE' : '')
               . implode(';', array_merge(array($hostname, $servicename), $parameters));
    }
}
