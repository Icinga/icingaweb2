<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

/**
 * Delete a host or service downtime
 */
class DeleteDowntimeCommand extends ObjectCommand
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
     * ID of the downtime that is to be deleted
     *
     * @var int
     */
    protected $downtimeId;

    /**
     * Name of the downtime (Icinga 2.4+)
     *
     * Required for removing the downtime via Icinga 2's API.
     *
     * @var string
     */
    protected $downtimeName;

    /**
     * Whether the command affects a service downtime
     *
     * @var boolean
     */
    protected $isService = false;

    /**
     * Get the ID of the downtime that is to be deleted
     *
     * @return int
     */
    public function getDowntimeId()
    {
        return $this->downtimeId;
    }

    /**
     * Set the ID of the downtime that is to be deleted
     *
     * @param   int $downtimeId
     *
     * @return  $this
     */
    public function setDowntimeId($downtimeId)
    {
        $this->downtimeId = (int) $downtimeId;
        return $this;
    }

    /**
     * Get the name of the downtime (Icinga 2.4+)
     *
     * Required for removing the downtime via Icinga 2's API.
     *
     * @return string
     */
    public function getDowntimeName()
    {
        return $this->downtimeName;
    }

    /**
     * Set the name of the downtime (Icinga 2.4+)
     *
     * Required for removing the downtime via Icinga 2's API.
     *
     * @param   string  $downtimeName
     *
     * @return  $this
     */
    public function setDowntimeName($downtimeName)
    {
        $this->downtimeName = $downtimeName;
        return $this;
    }

    /**
     * Get whether the command affects a service
     *
     * @deprecated Please add the object to the command instead of
     *             just marking it as service. This is required
     *             for instances to work!
     *
     * @return bool
     */
    public function getIsService()
    {
        return $this->isService;
    }

    /**
     * Set whether the command affects a service
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
