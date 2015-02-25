<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup;

use ArrayIterator;
use IteratorAggregate;

/**
 * Container to store and handle requirements
 */
class Requirements implements IteratorAggregate
{
    /**
     * The registered requirements
     *
     * @var array
     */
    protected $requirements = array();

    /**
     * Register a requirement
     *
     * @param   Requirement     $requirement    The requirement to add
     *
     * @return  Requirements
     */
    public function add(Requirement $requirement)
    {
        $merged = false;
        foreach ($this as $knownRequirement) {
            if ($requirement->equals($knownRequirement)) {
                if ($knownRequirement->isOptional() && !$requirement->isOptional()) {
                    $knownRequirement->setOptional(false);
                }

                foreach ($requirement->getDescriptions() as $description) {
                    $knownRequirement->addDescription($description);
                }

                $merged = true;
                break;
            }
        }

        if (! $merged) {
            $this->requirements[] = $requirement;
        }

        return $this;
    }

    /**
     * Return all registered requirements
     *
     * @return  array
     */
    public function getAll()
    {
        return $this->requirements;
    }

    /**
     * Return an iterator of all registered requirements
     *
     * @return  ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getAll());
    }

    /**
     * Register the given requirements
     *
     * @param   Requirements    $requirements   The requirements to register
     *
     * @return  Requirements
     */
    public function merge(Requirements $requirements)
    {
        foreach ($requirements as $requirement) {
            $this->add($requirement);
        }

        return $this;
    }

    /**
     * Return whether all mandatory requirements are fulfilled
     *
     * @return  bool
     */
    public function fulfilled()
    {
        foreach ($this as $requirement) {
            if (! $requirement->getState() && !$requirement->isOptional()) {
                return false;
            }
        }

        return true;
    }
}
