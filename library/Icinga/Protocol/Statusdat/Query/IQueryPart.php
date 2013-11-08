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

namespace Icinga\Protocol\Statusdat\Query;

/**
 * Class IQueryPart
 * @package Icinga\Protocol\Statusdat\Query
 */
interface IQueryPart
{
    /**
     * Create a new query part with an optional expression to be parse
     *
     * @param string $expression        An optional expression string to use
     * @param array $value              The values fot the optional expression
     */
    public function __construct($expression = null, &$value = array());

    /**
     * Filter the given resultset
     *
     * @param array $base           The resultset to use for filtering
     * @param array $idx            An optional array containing prefiltered indices
     */
    public function filter(array &$base, &$idx = null);

    /**
     * Add additional information about the query this filter belongs to
      *
     * @param $query
     * @return mixed
     */
    public function setQuery($query);

}
