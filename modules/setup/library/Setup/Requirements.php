<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Setup;

use ArrayIterator;
use IteratorAggregate;

/**
 * Container to store and handle requirements
 */
class Requirements implements IteratorAggregate
{
    /**
     * Identifier representing the state OK
     */
    const STATE_OK = 2;

    /**
     * Identifier representing the state OPTIONAL
     */
    const STATE_OPTIONAL = 1;

    /**
     * Identifier representing the state MANDATORY
     */
    const STATE_MANDATORY = 0;

    /**
     * The registered requirements
     *
     * @var array
     */
    protected $requirements = array();

    /**
     * Register a requirement
     *
     * @param   object  $requirement    The requirement to add
     *
     * @return  self
     */
    public function add($requirement)
    {
        $this->requirements[] = $requirement;
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
     * Register an optional requirement
     *
     * @param   string      $title
     * @param   string      $description
     * @param   bool        $state
     * @param   string      $message
     *
     * @return  self
     */
    public function addOptional($title, $description, $state, $message)
    {
        $this->add((object) array(
            'title'         => $title,
            'message'       => $message,
            'description'   => $description,
            'state'         => (bool) $state ? static::STATE_OK : static::STATE_OPTIONAL
        ));
        return $this;
    }

    /**
     * Register a mandatory requirement
     *
     * @param   string      $title
     * @param   string      $description
     * @param   bool        $state
     * @param   string      $message
     *
     * @return  self
     */
    public function addMandatory($title, $description, $state, $message)
    {
        $this->add((object) array(
            'title'         => $title,
            'message'       => $message,
            'description'   => $description,
            'state'         => (bool) $state ? static::STATE_OK : static::STATE_MANDATORY
        ));
        return $this;
    }

    /**
     * Register the given requirements
     *
     * @param   Requirements    $requirements   The requirements to register
     *
     * @return  self
     */
    public function merge(Requirements $requirements)
    {
        foreach ($requirements->getAll() as $requirement) {
            $this->add($requirement);
        }

        return $this;
    }

    /**
     * Make all registered requirements being optional
     *
     * @return  self
     */
    public function allOptional()
    {
        foreach ($this->getAll() as $requirement) {
            if ($requirement->state === static::STATE_MANDATORY) {
                $requirement->state = static::STATE_OPTIONAL;
            }
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
        foreach ($this->getAll() as $requirement) {
            if ($requirement->state === static::STATE_MANDATORY) {
                return false;
            }
        }

        return true;
    }
}
