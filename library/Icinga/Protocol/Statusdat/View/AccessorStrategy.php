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

namespace Icinga\Protocol\Statusdat\View;

/**
 * Interface for statusdat classes that provide a specific view on the dataset
 *
 * Views define special get and exists operations for fields that are not directly available
 * in a resultset, but exist under another name or can be accessed by loading an additional object
 * during runtime.
 *
 * @see Icinga\Backend\DataView\ObjectRemappingView  For an implementation of mapping field names
 * to storage specific names, e.g. service_state being status.current_state in status.dat views.
 *
 * @see Icinga\Backend\MonitoringObjectList For the typical usage of this class. It is not wrapped
 * around the monitoring object, so we don't use __get() or __set() and always have to give the
 * item we'd like to access.
 */
interface AccessorStrategy
{
    /**
     * Returns a field for the item, or throws an Exception if the field doesn't exist
     *
     * @param $item The item to access
     * @param $field The field of the item that should be accessed
     * @return string   The content of the field
     *
     * @throws \InvalidArgumentException when the field does not exist
     */
    public function get(&$item, $field);

    /**
     * Returns the name that the field has in the specific backend. Might not be available for every field/view
     * @param $field    The field name that should be translated
     * @return string   The real name of this field
     */
    public function getNormalizedFieldName($field);

    /**
     * Returns true if the field exists on the specific item, otherwise false
     *
     * @param $item     The item to access
     * @param $field    The field to check on the $item
     * @return bool  True when the field exists, otherwise false
     */
    public function exists(&$item, $field);
}
