<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

/**
 * Add a comment to a host or service
 */
class AddCommentCommand extends WithCommentCommand
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
     * Whether the comment is persistent
     *
     * Persistent comments are not lost the next time the monitoring host restarts.
     */
    protected $persistent;

    /**
     * Set whether the comment is persistent
     *
     * @param   bool $persistent
     *
     * @return  $this
     */
    public function setPersistent($persistent = true)
    {
        $this->persistent = $persistent;
        return $this;
    }

    /**
     * Is the comment persistent?
     *
     * @return bool
     */
    public function getPersistent()
    {
        return $this->persistent;
    }
}
