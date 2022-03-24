<?php
/* Icinga Web 2 | (c) 2022 Icinga Development Team | GPLv2+ */

namespace Icinga\Less;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use Less_Environment;
use Traversable;

/**
 * Registry for light modes and the environments in which they are defined
 */
class LightMode implements IteratorAggregate
{
    /** @var array Mode environments as mode-environment pairs */
    protected $envs = [];

    /** @var array Assoc list of modes */
    protected $modes = [];

    /** @var array Mode selectors as mode-selector pairs */
    protected $selectors = [];

    /**
     * @param string $mode
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the mode already exists
     */
    public function add($mode)
    {
        if (array_key_exists($mode, $this->modes)) {
            throw new InvalidArgumentException("$mode already exists");
        }

        $this->modes[$mode] = true;

        return $this;
    }

    /**
     * @param string $mode
     *
     * @return Less_Environment
     *
     * @throws InvalidArgumentException If there is no environment for the given mode
     */
    public function getEnv($mode)
    {
        if (! isset($this->envs[$mode])) {
            throw new InvalidArgumentException("$mode does not exist");
        }

        return $this->envs[$mode];
    }

    /**
     * @param string           $mode
     * @param Less_Environment $env
     *
     * @return $this
     *
     * @throws InvalidArgumentException If an environment for given the mode already exists
     */
    public function setEnv($mode, Less_Environment $env)
    {
        if (array_key_exists($mode, $this->envs)) {
            throw new InvalidArgumentException("$mode already exists");
        }

        $this->envs[$mode] = $env;

        return $this;
    }

    /**
     * @param string $mode
     *
     * @return bool
     */
    public function hasSelector($mode)
    {
        return isset($this->selectors[$mode]);
    }

    /**
     * @param string $mode
     *
     * @return string
     *
     * @throws InvalidArgumentException If there is no selector for the given mode
     */
    public function getSelector($mode)
    {
        if (! isset($this->selectors[$mode])) {
            throw new InvalidArgumentException("$mode does not exist");
        }

        return $this->selectors[$mode];
    }

    /**
     * @param string $mode
     * @param string $selector
     *
     * @return $this
     *
     * @throws InvalidArgumentException If a selector for given the mode already exists
     */
    public function setSelector($mode, $selector)
    {
        if (array_key_exists($mode, $this->selectors)) {
            throw new InvalidArgumentException("$mode already exists");
        }

        $this->selectors[$mode] = $selector;

        return $this;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator(array_keys($this->modes));
    }
}
