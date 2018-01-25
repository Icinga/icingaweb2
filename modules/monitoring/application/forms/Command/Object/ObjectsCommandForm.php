<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Command\Object;

use Icinga\Exception\ProgrammingError;
use Icinga\Module\Monitoring\Forms\Command\CommandForm;
use Icinga\Module\Monitoring\Object\MonitoredObject;

/**
 * Base class for Icinga object command forms
 */
abstract class ObjectsCommandForm extends CommandForm
{
    /**
     * Involved Icinga objects
     *
     * @var array|\Traversable|\ArrayAccess
     */
    protected $objects;

    /**
     * Set the involved Icinga objects
     *
     * @param   $objects MonitoredObject|array|\Traversable|\ArrayAccess
     *
     * @return  $this
     */
    public function setObjects($objects)
    {
        if ($objects instanceof MonitoredObject) {
            $this->objects = array($objects);
        } else {
            $this->objects = $objects;
        }
        return $this;
    }

    /**
     * Get the involved Icinga objects
     *
     * @return array|\ArrayAccess|\Traversable
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * Get the single object from set objects
     *
     * @return MonitoredObject|null
     * @throws ProgrammingError When more than one object set
     */
    public function getObject()
    {
        if (empty($this->objects)) {
            return null;
        } elseif (count($this->objects) > 1) {
            throw new ProgrammingError('More than one objects set to CommandForm!');
        } else {
            return current($this->objects);
        }
    }
}
