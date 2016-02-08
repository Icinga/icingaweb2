<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

/**
 * Base class for commands adding comments
 */
abstract class WithCommentCommand extends ObjectCommand
{
    /**
     * Author of the comment
     *
     * @var string
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
     * @param   string $author
     *
     * @return  $this
     */
    public function setAuthor($author)
    {
        $this->author = (string) $author;
        return $this;
    }

    /**
     * Get the author
     *
     * @return string
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
}
