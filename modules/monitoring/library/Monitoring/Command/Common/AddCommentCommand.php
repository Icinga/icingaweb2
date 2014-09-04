<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command\Common;

use Icinga\Module\Monitoring\Command\IcingaCommand;
use Icinga\User;

/**
 * Base class for commands adding comments
 */
abstract class AddCommentCommand extends IcingaCommand
{
    /**
     * Author of the comment
     *
     * @var User
     */
    protected $author;

    /**
     * Comment
     *
     * @var string
     */
    protected $comment;

    /**
     * Set the author
     *
     * @param   User $author
     *
     * @return  $this
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;
        return $this;
    }

    /**
     * Get the author
     *
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set the comment
     *
     * @param   string $comment
     *
     * @return  $this
     */
    public function setComment($comment)
    {
        $this->comment = (string) $comment;
        return $this;
    }

    /**
     * Get the comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\IcingaCommand::getCommandString() For the method documentation.
     */
    public function getCommandString()
    {
        return sprintf('%s;%s', $this->author->getUsername(), $this->comment);
    }
}
