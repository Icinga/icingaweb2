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


namespace Icinga\Filter\Type;

use Icinga\Filter\QueryProposer;

/**
 * A specific type of filter
 *
 * Implementations represent specific filters like text, monitoringstatus, time, flags, etc
 *
 */
abstract class FilterType extends QueryProposer
{
    /**
     * Return a list containing all operators that can appear in this filter type
     *
     * @return array            An array of strings
     */
    abstract public function getOperators();

    /**
     * Return true if the given query is valid for this type
     *
     * @param String $query     The query string to validate
     *
     * @return boolean          True when the query can be converted to a tree node, otherwise false
     */
    abstract public function isValidQuery($query);

    /**
     * Return a tree node representing the given query that can be inserted into a query tree
     *
     * @param String $query             The query to parse into a Node
     * @param String $leftOperand       The field to use for the left (target) side of the node
     *
     * @return Node                     A tree node
     */
    abstract public function createTreeNode($query, $leftOperand);

    /**
     * More verbose helper method for testing whether a string starts with the second one
     *
     * @param String $string        The string to use as the haystack
     * @param String $substring     The string to use as the needle
     *
     * @return bool                 True when $string starts with $substring
     */
    static public function startsWith($string, $substring)
    {
        return stripos($string, $substring) === 0;
    }

    /**
     * Get the operator that matches the given query best (i.e. the one with longest matching string)
     *
     * @param String $query     The query to extract the operator from
     *
     * @return string           The operator contained in this query or an empty string if no operator matches
     */
    protected function getMatchingOperatorForQuery($query)
    {
        $matchingOperator = '';
        foreach ($this->getOperators() as $operator) {
            if (stripos($query, $operator) === 0) {
                if (strlen($matchingOperator) < strlen($operator) ){
                    $matchingOperator = $operator;
                }
            }
        }
        return $matchingOperator;
    }
}