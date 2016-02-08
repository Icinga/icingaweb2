<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

/**
 * Acknowledge a host or service problem
 */
class AcknowledgeProblemCommand extends WithCommentCommand
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
     * Whether the acknowledgement is sticky
     *
     * Sticky acknowledgements remain until the host or service recovers. Non-sticky acknowledgements will be
     * automatically removed when the host or service state changes.
     *
     * @var bool
     */
    protected $sticky = false;

    /**
     * Whether to send a notification about the acknowledgement

     * @var bool
     */
    protected $notify = false;

    /**
     * Whether the comment associated with the acknowledgement is persistent
     *
     * Persistent comments are not lost the next time the monitoring host restarts.
     *
     * @var bool
     */
    protected $persistent = false;

    /**
     * Optional time when the acknowledgement should expire
     *
     * @var int|null
     */
    protected $expireTime;

    /**
     * Set whether the acknowledgement is sticky
     *
     * @param   bool $sticky
     *
     * @return  $this
     */
    public function setSticky($sticky = true)
    {
        $this->sticky = (bool) $sticky;
        return $this;
    }

    /**
     * Is the acknowledgement sticky?
     *
     * @return bool
     */
    public function getSticky()
    {
        return $this->sticky;
    }

    /**
     * Set whether to send a notification about the acknowledgement
     *
     * @param   bool $notify
     *
     * @return  $this
     */
    public function setNotify($notify = true)
    {
        $this->notify = (bool) $notify;
        return $this;
    }

    /**
     * Get whether to send a notification about the acknowledgement
     *
     * @return bool
     */
    public function getNotify()
    {
        return $this->notify;
    }

    /**
     * Set whether the comment associated with the acknowledgement is persistent
     *
     * @param   bool $persistent
     *
     * @return  $this
     */
    public function setPersistent($persistent = true)
    {
        $this->persistent = (bool) $persistent;
        return $this;
    }

    /**
     * Is the comment associated with the acknowledgement is persistent?
     *
     * @return bool
     */
    public function getPersistent()
    {
        return $this->persistent;
    }

    /**
     * Set the time when the acknowledgement should expire
     *
     * @param   int $expireTime
     *
     * @return  $this
     */
    public function setExpireTime($expireTime)
    {
        $this->expireTime = (int) $expireTime;
        return $this;
    }

    /**
     * Get the time when the acknowledgement should expire
     *
     * @return int|null
     */
    public function getExpireTime()
    {
        return $this->expireTime;
    }
}
