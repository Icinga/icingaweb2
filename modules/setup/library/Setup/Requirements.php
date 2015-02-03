<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Setup;

use ArrayIterator;
use IteratorAggregate;

/**
 * Container to store and handle requirements
 *
 * TODO: Requirements should be registered as objects with a specific purpose (PhpModRequirement, PhpIniRequirement, ..)
 *       so that it's not necessary to define unique identifiers which may differ between different modules.
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
    public function add($name, $requirement)
    {
        $this->requirements[$name] = array_key_exists($name, $this->requirements)
            ? $this->combine($this->requirements[$name], $requirement)
            : $requirement;
        return $this;
    }

    /**
     * Combine the two given requirements
     *
     * Returns the most important requirement with the description from the other one being added.
     *
     * @param   object  $oldRequirement
     * @param   object  $newRequirement
     *
     * @return  object
     */
    protected function combine($oldRequirement, $newRequirement)
    {
        if ($newRequirement->state === static::STATE_MANDATORY && $oldRequirement->state === static::STATE_OPTIONAL) {
            $tempRequirement = $oldRequirement;
            $oldRequirement = $newRequirement;
            $newRequirement = $tempRequirement;
        }

        if (! is_array($oldRequirement->description)) {
            $oldRequirement->description = array($oldRequirement->description);
        }

        $oldRequirement->description[] = $newRequirement->description;
        return $oldRequirement;
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
     * @param   string      $name
     * @param   string      $title
     * @param   string      $description
     * @param   bool        $state
     * @param   string      $message
     *
     * @return  self
     */
    public function addOptional($name, $title, $description, $state, $message)
    {
        $this->add(
            $name,
            (object) array(
                'title'         => $title,
                'message'       => $message,
                'description'   => $description,
                'state'         => (bool) $state ? static::STATE_OK : static::STATE_OPTIONAL
            )
        );
        return $this;
    }

    /**
     * Register a mandatory requirement
     *
     * @param   string      $name
     * @param   string      $title
     * @param   string      $description
     * @param   bool        $state
     * @param   string      $message
     *
     * @return  self
     */
    public function addMandatory($name, $title, $description, $state, $message)
    {
        $this->add(
            $name,
            (object) array(
                'title'         => $title,
                'message'       => $message,
                'description'   => $description,
                'state'         => (bool) $state ? static::STATE_OK : static::STATE_MANDATORY
            )
        );
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
        foreach ($requirements->getAll() as $name => $requirement) {
            $this->add($name, $requirement);
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
