<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup;

use LogicException;
use RecursiveIterator;

/**
 * Container to store and handle requirements
 */
class Requirements implements RecursiveIterator
{
    /**
     * Mode AND (all requirements must met)
     */
    const MODE_AND = 0;

    /**
     * Mode OR (at least one requirement must met)
     */
    const MODE_OR = 1;

    /**
     * The mode by with the requirements are evaluated
     *
     * @var string
     */
    protected $mode;

    /**
     * The registered requirements
     *
     * @var array
     */
    protected $requirements;

    /**
     * Whether there is any mandatory requirement part of this set
     *
     * @var bool
     */
    protected $containsMandatoryRequirements;

    /**
     * Create a new set of requirements
     *
     * @param   int     $mode   The mode by with to evaluate the requirements
     */
    public function __construct($mode = null)
    {
        $this->requirements = array();
        $this->containsMandatoryRequirements = false;
        $this->setMode($mode ?: static::MODE_AND);
    }

    /**
     * Set the mode by with to evaluate the requirements
     *
     * @param   int     $mode
     *
     * @return  Requirements
     *
     * @throws  LogicException      In case the given mode is invalid
     */
    public function setMode($mode)
    {
        if ($mode !== static::MODE_AND && $mode !== static::MODE_OR) {
            throw new LogicException(sprintf('Invalid mode %u given.'), $mode);
        }

        $this->mode = $mode;
        return $this;
    }

    /**
     * Return the mode by with the requirements are evaluated
     *
     * @return  int
     */
    public function getMode()
    {
        return $this->mode;
    }

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
        foreach ($this->requirements as $knownRequirement) {
            if ($knownRequirement instanceof Requirement && $requirement->equals($knownRequirement)) {
                if ($this->getMode() === static::MODE_AND && !$requirement->isOptional()) {
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
            if ($this->getMode() === static::MODE_OR) {
                $requirement->setOptional();
            } elseif (! $requirement->isOptional()) {
                $this->containsMandatoryRequirements = true;
            }

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
     * Return whether there is any mandatory requirement part of this set
     *
     * @return  bool
     */
    public function hasAnyMandatoryRequirement()
    {
        return $this->containsMandatoryRequirements || $this->getMode() === static::MODE_OR;
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
        if ($this->getMode() === static::MODE_OR && $requirements->getMode() === static::MODE_OR) {
            foreach ($requirements->getAll() as $requirement) {
                if ($requirement instanceof static) {
                    $this->merge($requirement);
                } else {
                    $this->add($requirement);
                }
            }
        } else {
            if ($requirements->getMode() === static::MODE_OR) {
                $this->containsMandatoryRequirements = true;
            }

            $this->requirements[] = $requirements;
        }

        return $this;
    }

    /**
     * Return whether all requirements can successfully be evaluated based on the current mode
     *
     * @return  bool
     */
    public function fulfilled()
    {
        $state = false;
        foreach ($this->requirements as $requirement) {
            if ($requirement instanceof static) {
                if ($requirement->fulfilled()) {
                    if ($this->getMode() === static::MODE_OR) {
                        return true;
                    }

                    $state = true;
                } elseif ($this->getMode() === static::MODE_AND && $requirement->hasAnyMandatoryRequirement()) {
                    return false;
                }
            } else {
                if ($requirement->getState()) {
                    if ($this->getMode() === static::MODE_OR) {
                        return true;
                    }

                    $state = true;
                } elseif ($this->getMode() === static::MODE_AND) {
                    if (! $requirement->isOptional()) {
                        return false;
                    }

                    $state = true; // There may only be optional requirements...
                }
            }
        }

        return $state;
    }

    /**
     * Return whether the current element represents a nested set of requirements
     *
     * @return  bool
     */
    public function hasChildren()
    {
        $current = $this->current();
        return $current instanceof static;
    }

    /**
     * Return a iterator for the current nested set of requirements
     *
     * @return  RecursiveIterator
     */
    public function getChildren()
    {
        return $this->current();
    }

    /**
     * Rewind the iterator to its first element
     */
    public function rewind()
    {
        reset($this->requirements);
    }

    /**
     * Return whether the current iterator position is valid
     *
     * @return  bool
     */
    public function valid()
    {
        return $this->key() !== null;
    }

    /**
     * Return the current element in the iteration
     *
     * @return  Requirement|Requirements
     */
    public function current()
    {
        return current($this->requirements);
    }

    /**
     * Return the position of the current element in the iteration
     *
     * @return  int
     */
    public function key()
    {
        return key($this->requirements);
    }

    /**
     * Advance the iterator to the next element
     */
    public function next()
    {
        next($this->requirements);
    }
}
