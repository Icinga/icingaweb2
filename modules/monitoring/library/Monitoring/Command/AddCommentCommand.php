<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command;

use Icinga\Protocol\Commandpipe\Command;
use Icinga\Protocol\Commandpipe\Comment;

/**
 * Icinga Command for adding comments
 *
 * @see Command
 */
class AddCommentCommand extends Command
{
    /**
     * The comment associated to this command
     *
     * @var Comment
     */
    private $comment;

    /**
     * Initialise a new command object to add comments
     *
     * @param   Comment $comment    The comment to use for this acknowledgement
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Set the comment for this command
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

    public function getArguments()
    {
        return $this->comment->getArguments();
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
        return sprintf('ADD_HOST_COMMENT;%s;', $hostname) . implode(';', $this->getArguments());
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
        return sprintf('ADD_SVC_COMMENT;%s;%s;', $hostname, $servicename)
            . implode(';', $this->getArguments());
    }
}
