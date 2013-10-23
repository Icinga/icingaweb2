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

namespace Icinga\Filter\Type;

use Icinga\Filter\Query\Node;

/**
 * Filter Type for specifying time points. Uses valid inputs for strtotime as the
 * Filter value
 *
 */
class TimeRangeSpecifier extends FilterType
{
    private $forcedPrefix = null;

    /**
     * Default operator to use
     *
     * @var array       A Text Token => Operator mapping for every supported operator
     */
    private $operator = array(
        'Since'     => Node::OPERATOR_GREATER_EQ,
        'Before'    => Node::OPERATOR_LESS_EQ
    );



    /**
     * Example values that will be displayed to the user
     *
     * @var array
     */
    public $timeExamples = array(
        '"5 minutes"',
        '"30 minutes"',
        '"1 hour"',
        '"6 hours"',
        '"1 day"',
        '"yesterday"',
        '"last Monday"'
    );

    /**
     * Return proposals for the given query part
     *
     * @param String $query    The part of the query that this specifier should parse
     * @return array            An array containing 0..* proposal text tokens
     */
    public function getProposalsForQuery($query)
    {
        if ($query === '') {
            return $this->getOperators();
        }
        $proposals = array();
        foreach ($this->getOperators() as $operator) {
            if (self::startsWith($query, $operator)) {
                if (!trim(substr($query, strlen($operator)))) {
                    $proposals = array_merge($proposals, $this->timeExamples);
                }
            } elseif (self::startsWith($operator, $query)) {
                $proposals[] = self::markDifference($operator, $query);
            }
        }
        return $proposals;
    }

    /**
     * Return an array containing the textual representation of all operators represented by this filter
     *
     * @return array        An array of operator string
     */
    public function getOperators()
    {
        return array_keys($this->operator);
    }


    /**
     * Return a two element array with the operator and the timestring parsed from the given query part
     *
     * @param  String $query        The query to extract the operator and time value from
     * @return array                An array containing the operator as the first and the string for
     *                              strotime as the second value or (null,null) if the query is invalid
     */
    private function getOperatorAndTimeStringFromQuery($query)
    {
        $currentOperator = null;
        foreach ($this->operator as $operator => $type) {
            if (self::startsWith($query, $operator)) {
                $currentOperator = $type;
                $query = trim(substr($query, strlen($operator)));
                break;
            }
        }
        $query = trim($query, '\'"');
        if (!$query || $currentOperator === null) {
            return array(null, null);
        }

        if (is_numeric($query[0])) {
            if ($this->forcedPrefix) {
                $prefix = $this->forcedPrefix;
            } elseif ($currentOperator === Node::OPERATOR_GREATER_EQ) {
                $prefix = '-';
            } else {
                $prefix = '+';
            }
            $query = $prefix . $query;
        }

        if (!strtotime($query)) {
            return array(null, null);
        }
        return array($currentOperator, $query);
    }

    /**
     * Return true if the query is valid, otherwise false
     *
     * @param String $query     The query string to validate
     * @return bool             True if the query is valid, otherwise false
     */
    public function isValidQuery($query)
    {
        list($operator, $timeQuery) = $this->getOperatorAndTimeStringFromQuery($query);
        return $timeQuery !== null;
    }

    /**
     * Create a query tree node representing the given query and using the field given as
     * $leftOperand as the attribute (left leaf of the tree)
     *
     * @param String $query             The query to create the node from
     * @param String $leftOperand       The attribute use for the node
     * @return Node|null
     */
    public function createTreeNode($query, $leftOperand)
    {
        list($operator, $timeQuery) = $this->getOperatorAndTimeStringFromQuery($query);

        if ($operator === null || $timeQuery === null) {
            return null;
        }
        $node =  Node::createOperatorNode($operator, $leftOperand, $timeQuery);
        $node->context = Node::CONTEXT_TIMESTRING;
        return $node;
    }

    /**
     * Set possible operators for this query, in a 'stringtoken' => NodeOperatorConstant map
     *
     * @param array $operator       The operator map to use
     * @return $this                Fluent interface
     */
    public function setOperator(array $operator)
    {
        $this->operator = $operator;
        return $this;
    }

    /**
     * Set all implicit values ('after 30 minutes') to be in the past ('after -30 minutes')
     *
     * @param  True $bool           True to set all timestring in the past
     * @return $this                Fluent interface
     */
    public function setForcePastValue($bool = true)
    {
        if ($bool) {
            $this->forcedPrefix = '-';
        } else {
            $this->forcedPrefix = null;
        }
        return $this;
    }

    /**
     * Set all implicit values ('after 30 minutes') to be in the future ('after +30 minutes')
     *
     * @param  True $bool           True to set all timestring in the future
     * @return $this                Fluent interface
     */
    public function setForceFutureValue($bool = true)
    {
        if ($bool) {
            $this->forcedPrefix = '+';
        } else {
            $this->forcedPrefix = null;
        }
        return $this;
    }
}
