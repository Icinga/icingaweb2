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

namespace Icinga\User\Preferences;

/**
 * Voyager object to transport changes between consumers
 */
class ChangeSet
{
    /**
     * Stack of pending updates
     *
     * @var array
     */
    private $update = array();

    /**
     * Stack of pending delete operations
     *
     * @var array
     */
    private $delete = array();

    /**
     * Stack of pending create operations
     *
     * @var array
     */
    private $create = array();

    /**
     * Push an update to stack
     *
     * @param string $key
     * @param mixed $value
     */
    public function appendUpdate($key, $value)
    {
        $this->update[$key] = $value;
    }

    /**
     * Getter for pending updates
     *
     * @return array
     */
    public function getUpdate()
    {
        return $this->update;
    }

    /**
     * Push delete operation to stack
     *
     * @param string $key
     */
    public function appendDelete($key)
    {
        $this->delete[] = $key;
    }

    /**
     * Get pending delete operations
     *
     * @return array
     */
    public function getDelete()
    {
        return $this->delete;
    }

    /**
     * Push create operation to stack
     *
     * @param string $key
     * @param mixed  $value
     */
    public function appendCreate($key, $value)
    {
        $this->create[$key] = $value;
    }

    /**
     * Get pending create operations
     *
     * @return array
     */
    public function getCreate()
    {
        return $this->create;
    }

    /**
     * Clear all changes
     */
    public function clear()
    {
        $this->update = array();
        $this->delete = array();
        $this->create = array();
    }

    /**
     * Test for registered changes
     *
     * @return bool
     */
    public function hasChanges()
    {
        return (count($this->update) > 0) || (count($this->delete) > 0) || (count($this->create));
    }
}
