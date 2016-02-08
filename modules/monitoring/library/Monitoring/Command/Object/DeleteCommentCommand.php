<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

use Icinga\Module\Monitoring\Command\IcingaCommand;

/**
 * Delete a host or service comment
 */
class DeleteCommentCommand extends IcingaCommand
{
    /**
     * ID of the comment that is to be deleted
     *
     * @var int
     */
    protected $commentId;

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
     * Get whether the command affects a service comment
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
     * @return  $this
     */
    public function setIsService($isService = true)
    {
        $this->isService = (bool) $isService;
        return $this;
    }
}
