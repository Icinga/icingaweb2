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

namespace Icinga\Web\Session;

use Icinga\Exception\NotImplementedError;

/**
 * Base class for handling sessions
 */
abstract class Session extends SessionNamespace
{
    /**
     * Container for session namespaces
     *
     * @var array
     */
    protected $namespaces = array();

    /**
     * The identifiers of all namespaces removed from this session
     *
     * @var array
     */
    protected $removedNamespaces = array();

    /**
     * Read all values from the underlying session implementation
     */
    abstract public function read();

    /**
     * Persists changes to the underlying session implementation
     */
    public function write() {
        throw new NotImplementedError('You are required to implement write() in your session implementation');
    }

    /**
     * Purge session
     */
    abstract public function purge();

    /**
     * Assign a new session id to this session.
     */
    abstract public function refreshId();

    /**
     * Get or create a new session namespace
     *
     * @param   string      $identifier     The namespace's identifier
     *
     * @return  SessionNamespace
     */
    public function getNamespace($identifier)
    {
        if (!isset($this->namespaces[$identifier])) {
            if (in_array($identifier, $this->removedNamespaces)) {
                unset($this->removedNamespaces[array_search($identifier, $this->removedNamespaces)]);
            }

            $this->namespaces[$identifier] = new SessionNamespace($this);
        }

        return $this->namespaces[$identifier];
    }

    /**
     * Return whether the given session namespace exists
     *
     * @param   string      $identifier     The namespace's identifier to check
     *
     * @return  bool
     */
    public function hasNamespace($identifier)
    {
        return isset($this->namespaces[$identifier]);
    }

    /**
     * Remove the given session namespace
     *
     * @param   string      $identifier     The identifier of the namespace to remove
     */
    public function removeNamespace($identifier)
    {
        unset($this->namespaces[$identifier]);
        $this->removedNamespaces[] = $identifier;
    }

    /**
     * Clear all values and namespaces from the session cache
     */
    public function clear()
    {
        $this->values = array();
        $this->removed = array();
        $this->namespaces = array();
        $this->removedNamespaces = array();
    }
}
