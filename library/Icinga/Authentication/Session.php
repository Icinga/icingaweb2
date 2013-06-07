<?php

namespace Icinga\Authentication;

abstract class Session
{
    private $sessionValues = array();
    
    
    abstract public function open();
    abstract public function read($keepOpen = false);
    abstract public function write($keepOpen = false);
    abstract public function close();
    abstract public function purge();

    public function set($key, $value)
    {
        $this->sessionValues[$key] = $value;
    }

    public function get($key, $defaultValue = null)
    {
        return isset($this->sessionValues[$key]) ?
            $this->sessionValues[$key] : $defaultValue;
    }

    public function getAll()
    {
        return $this->sessionValues;
    }

    public function setAll(array $values, $overwrite = false)
    {
        if ($overwrite) {
            $this->clear();
        }
        foreach ($values as $key => $value) {
            if (isset($this->sessionValues[$key]) && !$overwrite) {
                continue;
            }
            $this->sessionValues[$key] = $value;
        }
    }

    public function clear()
    {
        $this->sessionValues = array();
    }
}
