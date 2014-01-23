<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2014 Icinga Development Team
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
 * @copyright  2014 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Session;

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
     * Read all values from the underlying session implementation
     */
    abstract public function read();

    /**
     * Persists changes to the underlying session implementation
     */
    abstract public function write();

    /**
     * Purge session
     */
    abstract public function purge();

    /**
     * Setter for session values
     *
     * Values need to be manually persisted with method write.
     *
     * @param   string      $key        Name of value
     * @param   mixed       $value      Value to set
     * @param   string      $namespace  Namespace to use
     *
     * @return  Session
     * @see     self::write
     */
    public function set($key, $value, $namespace = null)
    {
        if ($namespace !== null) {
            if (!isset($this->sessionValues[$namespace])) {
                $this->sessionValues[$namespace] = array();
            }
            $this->sessionValues[$namespace][$key] = $value;
        } else {
            $this->sessionValues[$key] = $value;
        }

        return $this;
    }

    /**
     * Getter for session values
     *
     * Values are available after populating the session with method read.
     *
     * @param   string  $key            Name of the value to return
     * @param   mixed   $defaultValue   Default value to return
     * @param   string  $namespace      Namespace to use
     *
     * @return  mixed
     * @see     self::read
     */
    public function get($key, $defaultValue = null, $namespace = null)
    {
        if ($namespace !== null) {
            if (isset($this->sessionValues[$namespace]) && isset($this->sessionValues[$namespace][$key])) {
                return $this->sessionValues[$namespace][$key];
            }
            return $defaultValue;
        }

        return isset($this->sessionValues[$key]) ? $this->sessionValues[$key] : $defaultValue;
    }

    /**
     * Getter for all session values
     *
     * Values are available after populating the session with method read.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->sessionValues;
    }

    /**
     * Put an array into the session
     *
     * @param   array   $values     Values to set
     * @param   bool    $overwrite  Overwrite existing values
     * @param   strign  $namespace  Namespace to use
     */
    public function setAll(array $values, $overwrite = false, $namespace = null)
    {
        if ($namespace !== null && !isset($this->sessionValues[$namespace])) {
            $this->sessionValues[$namespace] = array();
        }

        foreach ($values as $key => $value) {
            if ($namespace !== null) {
                if (isset($this->sessionValues[$namespace][$key]) && !overwrite) {
                    continue;
                }
                $this->sessionValues[$namespace][$key] = $value;
            } else {
                if (isset($this->sessionValues[$key]) && !$overwrite) {
                    continue;
                }
                $this->sessionValues[$key] = $value;
            }
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
