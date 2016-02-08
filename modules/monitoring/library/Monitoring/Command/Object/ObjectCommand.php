<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Object;

use Icinga\Module\Monitoring\Command\IcingaCommand;
use Icinga\Module\Monitoring\Object\MonitoredObject;

/**
 * Base class for commands that involve a monitored object, i.e. a host or service
 */
abstract class ObjectCommand extends IcingaCommand
{
    /**
     * Type host
     */
    const TYPE_HOST = MonitoredObject::TYPE_HOST;

    /**
     * Type service
     */
    const TYPE_SERVICE = MonitoredObject::TYPE_SERVICE;

    /**
     * Allowed Icinga object types for the command
     *
     * @var string[]
     */
    protected $allowedObjects = array();

    /**
     * Involved object
     *
     * @var MonitoredObject
     */
    protected $object;

    /**
     * Set the involved object
     *
     * @param   MonitoredObject $object
     *
     * @return  $this
     */
    public function setObject(MonitoredObject $object)
    {
        $object->assertOneOf($this->allowedObjects);
        $this->object = $object;
        return $this;
    }

    /**
     * Get the involved object
     *
     * @return MonitoredObject
     */
    public function getObject()
    {
        return $this->object;
    }
}
