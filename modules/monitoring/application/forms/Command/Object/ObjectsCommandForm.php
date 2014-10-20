<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command\Object;

use Icinga\Module\Monitoring\Form\Command\CommandForm;
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
}
