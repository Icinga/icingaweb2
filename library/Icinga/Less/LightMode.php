<?php

namespace Icinga\Less;

use InvalidArgumentException;
use Less_Environment;

/**
 * Registry for light modes and the environments in which they are defined
 */
class LightMode
{
    protected $envs = [];

    protected $modes = [];

    public function add($mode, $module = null)
    {
        if (array_key_exists($mode, $this->modes)) {
            throw new InvalidArgumentException("$mode already exists");
        }

        $this->modes[$mode] = $module ?: null;

        return $this;
    }

    public function isModule($mode)
    {
        return isset($this->modes[$mode]);
    }

    public function list()
    {
        $modes = [];

        foreach ($this->modes as $mode => $module) {
            $modes[$module][] = $mode;
        }

        $byModule = $modes;
        unset($byModule[null]);

        return [isset($modes[null]) ? $modes[null] : [], $byModule];
    }

    public function getEnv($mode)
    {
        if (! isset($this->envs[$mode])) {
            throw new InvalidArgumentException("$mode does not exist");
        }

        return $this->envs[$mode];
    }

    public function setEnv($mode, Less_Environment $env)
    {
        if (array_key_exists($mode, $this->envs)) {
            throw new InvalidArgumentException("$mode already exists");
        }

        $this->envs[$mode] = $env;

        return $this;
    }
}
