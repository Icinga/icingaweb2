<?php

namespace Icinga\Objects;

class Object
{
    protected $id;
    protected $name;
    protected $props;
    protected $defaults = array();
    protected $fromBackend = false;
    protected $hasBeenChanged = false;

    protected function __construct($props = array())
    {
        $this->props = $this->defaults;
        if (! empty($props)) {
            $this->setProperties($props);
        }
    }

    public function setProperties($props)
    {
        foreach ($props as $key => $val) {
            $this->props[$key] = $val;
        }
    }

    protected function set($key, $val)
    {
        $this->props[$key] = $val;
        return $this;
    }

    public function __set($key, $val)
    {
        $this->set($key, $val);
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->props)) {
            return $this->props[$key];
        }
        return null;
    }

    protected function setLoadedFromBackend($loaded = true)
    {
        $this->fromBackend = $loaded;
        return $this;
    }

    public static function fromBackend($row)
    {
        $class = get_called_class();
        $object = new $class($row);
        $object->setLoadedFromBackend();
        return $object;
    }
}
