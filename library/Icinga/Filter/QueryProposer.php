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
 * Base class for Query proposers
 *
 * Query Proposer accept an query string in their getProposalsForQuery method and return
 * possible parts to complete this query
 */
abstract class QueryProposer
{
    /**
     * Static helper function to encapsulate similar string parts with an {}
     *
     * @param $attribute        The attribute to mark differences in
     * @param $query            The query to use for determining similarities
     *
     * @return string           The attribute string with similar parts encapsulated in curly braces
     */
    public static function markDifference($attribute, $query)
    {
        if (strlen($query) === 0) {
            return $attribute;
        }
        return '{' . substr($attribute, 0, strlen($query)) . '}' . substr($attribute, strlen($query));
    }

    /**
     * Return proposals for the given query part
     *
     * @param String $query    The part of the query that this specifier should parse
     *
     * @return array            An array containing 0..* proposal text tokens
     */
    abstract public function getProposalsForQuery($query);
}
