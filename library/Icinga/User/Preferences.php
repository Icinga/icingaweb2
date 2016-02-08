<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\User;

use Countable;

/**
 * User preferences container
 *
 * Usage example:
 * <code>
 * <?php
 *
 * use Icinga\User\Preferences;
 *
 * $preferences = new Preferences(); // Start with empty preferences
 *
 * $preferences = new Preferences(array('aPreference' => 'value')); // Start with initial preferences
 *
 * $preferences->aNewPreference = 'value'; // Set a preference
 *
 * unset($preferences->aPreference); // Unset a preference
 *
 * // Retrieve a preference and return a default value if the preference does not exist
 * $anotherPreference = $preferences->get('anotherPreference', 'defaultValue');
 */
class Preferences implements Countable
{
    /**
     * Preferences key-value array
     *
     * @var array
     */
    protected $preferences = array();

    /**
     * Constructor
     *
     * @param   array   $preferences    Preferences key-value array
     */
    public function __construct(array $preferences = array())
    {
        $this->preferences = $preferences;
    }

    /**
     * Count all preferences
     *
     * @return  int     The number of preferences
     */
    public function count()
    {
        return count($this->preferences);
    }

    /**
     * Determine whether a preference exists
     *
     * @param   string      $name
     *
     * @return  bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->preferences);
    }

    /**
     * Write data to a preference
     *
     * @param   string      $name
     * @param   mixed       $value
     */
    public function __set($name, $value)
    {
        $this->preferences[$name] = $value;
    }

    /**
     * Retrieve a preference section
     *
     * @param   string      $name
     *
     * @return  array|null
     */
    public function get($name)
    {
        if (array_key_exists($name, $this->preferences)) {
            return $this->preferences[$name];
        }

        return null;
    }

    /**
     * Retrieve a value from a specific section
     *
     * @param string    $section
     * @param string    $name
     * @param null      $default
     *
     * @return array|null
     */
    public function getValue($section, $name, $default = null)
    {
        if (array_key_exists($section, $this->preferences)
            && array_key_exists($name, $this->preferences[$section])
        ) {
            return $this->preferences[$section][$name];
        }

        return $default;
    }

    /**
     * Magic method so that $obj->value will work.
     *
     * @param   string      $name
     *
     * @return  mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Remove a given preference
     *
     * @param   string      $name   Preference name
     */
    public function remove($name)
    {
        unset($this->preferences[$name]);
    }

    /**
     * Determine if a preference is set and is not NULL
     *
     * @param   string      $name   Preference name
     *
     * @return  bool
     */
    public function __isset($name)
    {
        return isset($this->preferences[$name]);
    }

    /**
     * Unset a given preference
     *
     * @param   string      $name   Preference name
     */
    public function __unset($name)
    {
        $this->remove($name);
    }

    /**
     * Get preferences as array
     *
     * @return  array
     */
    public function toArray()
    {
        return $this->preferences;
    }
}
