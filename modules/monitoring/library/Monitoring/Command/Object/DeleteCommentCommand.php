<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Monitoring\Command\Object;

/**
 * Delete a host or service comment
 */
class DeleteCommentCommand extends ObjectCommand
{
    /**
     * (non-PHPDoc)
     * @see \Icinga\Module\Monitoring\Command\Object\ObjectCommand::$allowedObjects For the property documentation.
     */
    protected $allowedObjects = array(
        self::TYPE_HOST,
        self::TYPE_SERVICE
    );

    /**
     * ID of the comment that is to be deleted
     *
     * @var int
     */
    protected $commentId;

    /**
     * Set the ID of the comment that is to be deleted
     *
     * @param   int $commentId
     *
     * @return  $this
     */
    public function setCommentId($commentId)
    {
        $this->commentId = (int) $commentId;
        return $this;
    }

    /**
     * Get the ID of the comment that is to be deleted
     *
     * @return int
     */
    public function getCommentId()
    {
        return $this->commentId;
    }
}
