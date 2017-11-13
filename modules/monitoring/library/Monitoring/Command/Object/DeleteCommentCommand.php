<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

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
     * Name of the comment (Icinga 2.4+)
     *
     * Required for removing the comment via Icinga 2's API.
     *
     * @var string
     */
    protected $commentName;

    /**
     * Whether the command affects a service comment
     *
     * @var boolean
     */
    protected $isService = false;

    /**
     * Get the ID of the comment that is to be deleted
     *
     * @return int
     */
    public function getCommentId()
    {
        return $this->commentId;
    }

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
     * Get the name of the comment (Icinga 2.4+)
     *
     * Required for removing the comment via Icinga 2's API.
     *
     * @return string
     */
    public function getCommentName()
    {
        return $this->commentName;
    }

    /**
     * Set the name of the comment (Icinga 2.4+)
     *
     * Required for removing the comment via Icinga 2's API.
     *
     * @param   string  $commentName
     *
     * @return  $this
     */
    public function setCommentName($commentName)
    {
        $this->commentName = $commentName;
        return $this;
    }

    /**
     * Get whether the command affects a service comment
     *
     * @deprecated Please add the object to the command instead of
     *             just marking it as service. This is required
     *             for instances to work!
     *
     * @return boolean
     */
    public function getIsService()
    {
        return $this->isService;
    }

    /**
     * Set whether the command affects a service comment
     *
     * @param   bool $isService
     *
     * @deprecated Please add the object to the command instead of
     *             just marking it as service. This is required
     *             for instances to work!
     *
     * @return  $this
     */
    public function setIsService($isService = true)
    {
        $this->isService = (bool) $isService;
        return $this;
    }
}
