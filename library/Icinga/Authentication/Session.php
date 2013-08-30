<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 * 
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication;

/**
 * Base class for handling sessions
 */
abstract class Session
{
    /**
     * Container for session values
     *
     * @var array
     */
    private $sessionValues = array();

    /**
     * Open a session or creates a new one if not exists
     */
    abstract public function open();

    /**
     * Read all values from the underlying session implementation
     *
     * @param bool $keepOpen True to keep the session open
     */
    abstract public function read($keepOpen = false);

    /**
     * Persists changes to the underlying session implementation
     *
     * @param bool $keepOpen True to keep the session open
     */
    abstract public function write($keepOpen = false);

    /**
     * Close session
     */
    abstract public function close();

    /**
     * Purge session
     */
    abstract public function purge();

    /**
     * Setter for session values
     *
     * You have to persist values manually
     *
     * @see     self::persist
     * @param   string    $key    Name of value
     * @param   mixed     $value  Value
     */
    public function set($key, $value)
    {
        $this->sessionValues[$key] = $value;
    }

    /**
     * Getter fpr session values
     *
     * Values are available after populate session with method read.
     *
     * @param   string  $key
     * @param   mixed   $defaultValue
     *
     * @return  mixed
     * @see     self::read
     */
    public function get($key, $defaultValue = null)
    {
        return isset($this->sessionValues[$key]) ?
            $this->sessionValues[$key] : $defaultValue;
    }

    /**
     * Getter for all session values
     *
     * This are also dirty, unwritten values.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->sessionValues;
    }

    /**
     * Put an array into session
     *
     * @param array $values
     * @param bool  $overwrite  Overwrite existing values
     */
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
     * Clear all values from the session cache
     */
    public function clear()
    {
        $this->sessionValues = array();
    }
}
