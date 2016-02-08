<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup;

use LogicException;
use RecursiveIterator;

/**
 * Container to store and handle requirements
 */
class RequirementSet implements RecursiveIterator
{
    /**
     * Mode AND (all requirements must be met)
     */
    const MODE_AND = 0;

    /**
     * Mode OR (at least one requirement must be met)
     */
    const MODE_OR = 1;

    /**
     * Whether all requirements meet their condition
     *
     * @var bool
     */
    protected $state;

    /**
     * Whether this set is optional
     *
     * @var bool
     */
    protected $optional;

    /**
     * The mode by which the requirements are evaluated
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
     * The raw state of this set's requirements
     *
     * @var bool
     */
    private $forcedState;

    /**
     * Initialize a new set of requirements
     *
     * @param   bool    $optional   Whether this set is optional
     * @param   int     $mode       The mode by which to evaluate this set
     */
    public function __construct($optional = false, $mode = null)
    {
        $this->optional = $optional;
        $this->requirements = array();
        $this->setMode($mode ?: static::MODE_AND);
    }

    /**
     * Set the state of this set
     *
     * @param   bool    $state
     *
     * @return  RequirementSet
     */
    public function setState($state)
    {
        $this->state = (bool) $state;
        return $this;
    }

    /**
     * Return the state of this set
     *
     * Alias for RequirementSet::fulfilled(true).
     *
     * @return  bool
     */
    public function getState()
    {
        return $this->fulfilled(true);
    }

    /**
     * Set whether this set of requirements should be optional
     *
     * @param   bool    $state
     *
     * @return  RequirementSet
     */
    public function setOptional($state = true)
    {
        $this->optional = (bool) $state;
        return $this;
    }

    /**
     * Return whether this set of requirements is optional
     *
     * @return  bool
     */
    public function isOptional()
    {
        return $this->optional;
    }

    /**
     * Set the mode by which to evaluate the requirements
     *
     * @param   int     $mode
     *
     * @return  RequirementSet
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
     * Return the mode by which the requirements are evaluated
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
     * @return  RequirementSet
     */
    public function add(Requirement $requirement)
    {
        $merged = false;
        foreach ($this->requirements as $knownRequirement) {
            if ($knownRequirement instanceof Requirement && $requirement->equals($knownRequirement)) {
                $knownRequirement->setOptional($requirement->isOptional());
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
     * Register the given set of requirements
     *
     * @param   RequirementSet  $set    The set to register
     *
     * @return  RequirementSet
     */
    public function merge(RequirementSet $set)
    {
        if ($this->getMode() === $set->getMode() && $this->isOptional() === $set->isOptional()) {
            foreach ($set->getAll() as $requirement) {
                if ($requirement instanceof static) {
                    $this->merge($requirement);
                } else {
                    $this->add($requirement);
                }
            }
        } else {
            $this->requirements[] = $set;
        }

        return $this;
    }

    /**
     * Return whether all requirements can successfully be evaluated based on the current mode
     *
     * In case this is a optional set of requirements (and $force is false), true is returned immediately.
     *
     * @param   bool    $force      Whether to ignore the optionality of a set or single requirement
     *
     * @return  bool
     */
    public function fulfilled($force = false)
    {
        $state = $this->isOptional();
        if (! $force && $state) {
            return true;
        }

        if (! $force && $this->state !== null) {
            return $this->state;
        } elseif ($force && $this->forcedState !== null) {
            return $this->forcedState;
        }

        $self = $this->requirements;
        foreach ($self as $requirement) {
            if ($requirement->getState()) {
                $state = true;
                if ($this->getMode() === static::MODE_OR) {
                    break;
                }
            } elseif ($force || !$requirement->isOptional()) {
                $state = false;
                if ($this->getMode() === static::MODE_AND) {
                    break;
                }
            }
        }

        if ($force) {
            return $this->forcedState = $state;
        }

        return $this->state = $state;
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
     * @return  Requirement|RequirementSet
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

    /**
     * Return this set of requirements rendered as HTML
     *
     * @return  string
     */
    public function __toString()
    {
        $renderer = new RequirementsRenderer($this);
        return (string) $renderer;
    }
}
