<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication;

/**
*   Base class for session, providing getter, setters and required
*   interface methods
*   
**/
abstract class Session
{
    private $sessionValues = array();

    /**
    *   Opens a session or creates a new one if not exists
    *
    **/
    abstract public function open();

    /**
    *   Reads all values from the underyling session implementation
    *
    *   @param Boolean $keepOpen    True to keep the session open (depends on implementaiton)
    **/
    abstract public function read($keepOpen = false);
    
    /**
    *   Persists changes to the underlying session implementation
    *   
    *   @param Boolean $keepOpen    True to keep the session open (depends on implementaiton)
    **/
    abstract public function write($keepOpen = false);
    abstract public function close();
    abstract public function purge();

    /**
    *   Sets a $value under the provided key in the internal session data array
    *   Does not persist those changes, use @see Session::write in order to persist the changes
    *   made here.
    *
    *   @param  String  $key
    *   @param  mixed   $value
    **/
    public function set($key, $value)
    {
        $this->sessionValues[$key] = $value;
    }

    /**
    *   Returns the session value stored under $key or $defaultValue if not found. 
    *   call @see Session:read in order to populate this array with the underyling session implementation
    *
    *   @param  String  $key
    *   @param  mixed   $defaultValue
    *
    *   @return mixed
    **/
    public function get($key, $defaultValue = null)
    {
        return isset($this->sessionValues[$key]) ?
            $this->sessionValues[$key] : $defaultValue;
    }

    /**
    *   Returns the current session value state (also dirty changes not yet written to the session)
    *   
    *   @return Array
    **/
    public function getAll()
    {
        return $this->sessionValues;
    }

    /**
    *   Writes all values provided in the key=>value array to the internal session value state.
    *   In order to persist these chages, call @see Session:write
    *   
    *   @param  Array   $values
    *   @param  Boolean $overwrite      Whether to overwrite already set values 
    **/
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
    
    /**
    *  Clears all values from the session cache 
    *
    **/
    public function clear()
    {
        $this->sessionValues = array();
    }
}
