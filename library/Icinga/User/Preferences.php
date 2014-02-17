<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

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
 * $prefrences = $user->getPreferences(); // Retrieve preferences from a \Icinga\User instance
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
    private $preferences = array();

    /**
     * Constructor
     *
     * @param array $preferences Preferences key-value array
     */
    public function __construct(array $preferences = array())
    {
        $this->preferences = $preferences;
    }

    /**
     * Count all preferences
     *
     * @return int The number of preferences
     */
    public function count()
    {
        return count($this->preferences);
    }

    /**
     * Determine whether a preference exists
     *
     * @param   string $name
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
     * @param  string $name
     * @param  mixed  $value
     */
    public function __set($name, $value)
    {
        $this->preferences[$name] = $value;
    }

    /**
     * Retrieve a preference and return $default if the preference is not set
     *
     * @param   string  $name
     * @param   mixed   $default
     *
     * @return  mixed
     */
    public function get($name, $default = null)
    {
        if (array_key_exists($name, $this->preferences)) {
            return $this->preferences[$name];
        }
        return $default;
    }

    /**
     * Magic method so that $obj->value will work.
     *
     * @param   string $name
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
     * @param string $name Preference name
     */
    public function remove($name)
    {
        unset($this->preferences[$name]);
    }

    /**
     * Determine if a preference is set and is not NULL
     *
     * @param   string $name Preference name
     * @return  bool
     */
    public function __isset($name)
    {
        return isset($this->preferences[$name]);
    }

    /**
     * Unset a given preference
     *
     * @param string $name Preference name
     */
    public function __unset($name)
    {
        $this->remove($name);
    }

    /**
     * Get preferences as array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->preferences;
    }
}
