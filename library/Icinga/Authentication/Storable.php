<?php

/**
 * Icinga Authentication Storable class
 *
 * @package Icinga\Authentication
 */
namespace Icinga\Authentication;

/**
 * This class represents an abstract storable object
 *
 * Use this only for objects with unique identifiers. Do not persist such
 * objects, they shall be loaded at each request. Storable doesn't care about
 * race conditions and doesn't care about the current data in your backend.
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package    Icinga\Application
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
abstract class Storable
{
    protected $key;

    /**
     * Current Storable properties
     */
    protected $props;

    /**
     * Default property values for this Storable
     *
     * All allowed properties have to be defined here, otherwise they will be
     * rejected
     */
    protected $defaultProps = array();

    /**
     * Properties as they have been once loaded from backend
     */
    protected $storedProps = array();

    /**
     * Whether this storable has been stored in the current state
     */
    protected $stored = false;

    /**
     * Create a new Storable instance, with data loaded from backend
     *
     * You should NEVER directly use this function unless you are absolutely
     * sure on what you are doing.
     *
     * @param  Backend  The backend used to load this object from
     * @param  Array    Property array
     * @return Storable
     */
    public static function create(UserBackend $backend, $props = array())
    {
        $class = get_called_class();
        $object = new $class($props);
        return $object;
    }

    /**
     * Override this function for custom cross-value checks before storing it
     *
     * @return boolean  Whether the Storable is valid
     */
    public function isValid()
    {
        return true;
    }

    /**
     * The constructor is protected, you should never override it
     *
     * Use the available hooks for all the things you need to do at construction
     * time
     *
     * @param  Array    Property array
     * @return void
     */
    final protected function __construct($properties = array())
    {
        $this->assertKeyHasBeenDefined();
        $this->props = $this->defaultProps;
        foreach ($properties as $key => $val) {
            $this->set($key, $val);
        }
        $this->assertKeyExists();
    }


    /**
     * Get property value, fail unless it exists
     *
     * @param  string Property name
     * @return mixed
     */
    public function get($key)
    {
        $this->assertPropertyExists($key);
        return $this->props[$key];
        return $this;
    }

    /**
     * Set property value, fail unless it exists
     *
     * @param  string Property name
     * @param  mixed  New property value
     * @return Storable
     */
    protected function set($key, $val)
    {
        $this->assertPropertyExists($key);
        $this->props[$key] = $val;
        return $this;
    }

    /**
     * Getter
     *
     * @param  string Property name
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Setter
     *
     * @param  string Property name
     * @param  mixed  New property value
     * @return void
     */
    public function __set($key, $val)
    {
        $this->set($key, $val);
    }

    /**
     * Whether the given property name exist
     *
     * @param  string Property name
     * @return boolean
     */
    public function __isset($key)
    {
        return array_key_exists($key, $this->props);
    }

    /**
     * Makes sure that the Storable got it's unique key
     *
     * @throws \Exception
     * @return Storable
     */
    protected function assertKeyExists()
    {
        return $this->assertPropertyExists($this->key);
    }

    /**
     * Makes sure the given property is allowed
     *
     * @throws \Exception
     * @return Storable
     */
    protected function assertPropertyExists($key)
    {
        if (! array_key_exists($key, $this->props)) {
            throw new \Exception(
                sprintf(
                    'Storable (%s) has no "%s" property',
                    get_class($this),
                    $key
                )
            );
        }
        return $this;
    }

    /**
     * Makes sure that the class inheriting Storable defined it's key column
     *
     * @throws \Exception
     * @return Storable
     */
    protected function assertKeyHasBeenDefined()
    {
        if ($this->key === null) {
            throw new \Exception(
                'Implementation error, Storable needs a valid key'
            );
        }
        return $this;
    }
}
