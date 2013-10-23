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


namespace Icinga\Filter;

/**
 * Interface for filterable data sources
 */
interface Filterable
{
    /**
     * Return true when this field is filterable, otherwise false
     *
     * @param  string $field        The field to test for being filterable
     * @return boolean              True when this field is filterable, otherwise false
     */
    public function isValidFilterTarget($field);

    /**
     * Return the internal, resolved name of the given field
     *
     * @param  string $field        The field to resolve
     * @return string               The resolved name or null if the field is not resolvable
     */
    public function getMappedField($field);

    /**
     * Apply all filters of this filterable on the datasource
     */
    public function applyFilter();

    /**
     * Remove all filters from this datasource
     */
    public function clearFilter();

    /**
     * Add a filter to this datasource
     *
     * @param  mixed $filter        The filter to use
     */
    public function addFilter($filter);
}
