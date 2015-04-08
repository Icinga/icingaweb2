<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

use Iterator;
use Countable;
use ArrayAccess;
use LogicException;

/**
 * Container for configuration values
 */
class ConfigObject implements Countable, Iterator, ArrayAccess
{
    /**
     * This config's data
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new config
     *
     * @param   array   $data   The data to initialize the new config with
     */
    public function __construct(array $data = array())
    {
        $this->data = array();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->data[$key] = new static($value);
            } else {
                $this->data[$key] = $value;
            }
        }
    }

    /**
     * Deep clone this config
     */
    public function __clone()
    {
        $array = array();
        foreach ($this->data as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = clone $value;
            } else {
                $array[$key] = $value;
            }
        }

        $this->data = $array;
    }

    /**
     * Return the count of available sections and properties
     *
     * @return  int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Reset the current position of $this->data
     *
     * @return  mixed
     */
    public function rewind()
    {
        return reset($this->data);
    }

    /**
     * Return the section's or property's value of the current iteration
     *
     * @return  mixed
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * Return whether the position of the current iteration is valid
     *
     * @return  bool
     */
    public function valid()
    {
        return key($this->data) !== null;
    }

    /**
     * Return the section's or property's name of the current iteration
     *
     * @return  mixed
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * Advance the position of the current iteration and return the new section's or property's value
     *
     * @return  mixed
     */
    public function next()
    {
        return next($this->data);
    }

    /**
     * Return whether the given section or property is set
     *
     * @param   string  $key    The name of the section or property
     *
     * @return  bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Return the value for the given property or the config for the given section
     *
     * @param   string  $key    The name of the property or section
     *
     * @return  mixed|NULL      The value or NULL in case $key does not exist
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Add a new property or section
     *
     * @param   string  $key    The name of the new property or section
     * @param   mixed   $value  The value to set for the new property or section
     */
    public function __set($key, $value)
    {
        if (is_array($value)) {
            $this->data[$key] = new static($value);
        } else {
            $this->data[$key] = $value;
        }
    }

    /**
     * Remove the given property or section
     *
     * @param   string  $key    The property or section to remove
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Return whether the given section or property is set
     *
     * @param   string  $key    The name of the section or property
     *
     * @return  bool
     */
    public function offsetExists($key)
    {
        return isset($this->$key);
    }

    /**
     * Return the value for the given property or the config for the given section
     *
     * @param   string  $key    The name of the property or section
     *
     * @return  mixed|NULL      The value or NULL in case $key does not exist
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Add a new property or section
     *
     * @param   string  $key    The name of the new property or section
     * @param   mixed   $value  The value to set for the new property or section
     */
    public function offsetSet($key, $value)
    {
        if ($key === null) {
            throw new LogicException('Appending values without an explicit key is not supported');
        }

        $this->$key = $value;
    }

    /**
     * Remove the given property or section
     *
     * @param   string  $key    The property or section to remove
     */
    public function offsetUnset($key)
    {
        unset($this->$key);
    }

    /**
     * Return whether this config has any data
     *
     * @return  bool
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

    /**
     * Return the value for the given property or the config for the given section
     *
     * @param   string  $key        The name of the property or section
     * @param   mixed   $default    The value to return in case the property or section is missing
     *
     * @return  mixed
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return $default !== null ? $default : null;
    }

    /**
     * Return all section and property names
     *
     * @return  array
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * Return this config's data as associative array
     *
     * @return  array
     */
    public function toArray()
    {
        $array = array();
        foreach ($this->data as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * Merge the given data with this config
     *
     * @param   array|Config    $data   An array or a config
     *
     * @return  $this
     */
    public function merge($data)
    {
        if ($data instanceof self) {
            $data = $data->toArray();
        }

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->data)) {
                if (is_array($value)) {
                    if ($this->data[$key] instanceof self) {
                        $this->data[$key]->merge($value);
                    } else {
                        $this->data[$key] = new static($value);
                    }
                } else {
                    $this->data[$key] = $value;
                }
            } else {
                $this->data[$key] = is_array($value) ? new static($value) : $value;
            }
        }

        return $this;
    }
}
