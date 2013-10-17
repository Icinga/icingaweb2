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

use Icinga\Filter\Query\Node;

/**
 * Boolean filter for setting flag filters (host is in problem state)
 *
 */
class BooleanFilter extends FilterType
{
    /**
     * The operqator map to use
     *
     * @var array
     */
    private $operators = array(
        Node::OPERATOR_EQUALS => 'Is',
        Node::OPERATOR_EQUALS_NOT => 'Is Not'
    );

    /**
     * The fields that are supported by this filter
     *
     * These fields somehow break the mechanismn as they overwrite the field given in the
     * Attribute
     *
     * @var array
     */
    private $fields = array();

    /**
     * An TimeRangeSpecifier if a field is given
     *
     * @var TimeRangeSpecifier
     */
    private $subFilter;

    /**
     * An optional field to use for time information (no time filters are possible if this is not given)
     *
     * @var string
     */
    private $timeField;

    /**
     * Create a new Boolean Filter handling the given field mapping
     *
     * @param array $fields         The fields to use, in a internal_key => Text token mapping
     * @param String $timeField     An optional time field, allows time specifiers to be appended to the query if given
     */
    public function __construct(array $fields, $timeField = false)
    {
        $this->fields = $fields;
        if (is_string($timeField)) {
            $this->subFilter = new TimeRangeSpecifier();
            $this->timeField = $timeField;
        }
    }

    /**
     * Overwrite the text to use for operators
     *
     * @param String $positive      The 'set flag' operator (default: 'is')
     * @param String $negative      The 'unset flag' operator (default: 'is not')
     */
    public function setOperators($positive, $negative)
    {
        $this->operators = array(
            Node::OPERATOR_EQUALS       =>  $positive,
            Node::OPERATOR_EQUALS_NOT   =>  $negative
        );
    }

    /**
     * Return a proposal for completing a field given the $query string
     *
     * @param  String $query        The query to get the proposal from
     * @return array                An array containing text tokens that could be used for completing the query
     */
    private function getFieldProposals($query)
    {
        $proposals = array();
        foreach ($this->fields as $key => $field) {
            $match = null;
            if (self::startsWith($field, $query)) {
                $match = $field;
            } elseif (self::startsWith($key, $query)) {
                $match = $key;
            } else {
                continue;
            }

            if (self::startsWith($query, $match) && $this->subFilter) {
                $subQuery = trim(substr($query, strlen($match)));
                $proposals = $proposals + $this->subFilter->getProposalsForQuery($subQuery);
            } elseif (strtolower($query) !== strtolower($match)) {
                $proposals[] = self::markDifference($match, $query);
            }
        }
        return $proposals;
    }

    /**
     * Return proposals for the given query part
     *
     * @param String $query    The part of the query that this specifier should parse
     *
     * @return array            An array containing 0..* proposal text tokens
     */
    public function getProposalsForQuery($query)
    {
        $proposals = array();
        $operators = $this->getOperators();
        if ($query === '') {
            return $this->getOperators();
        }

        foreach ($operators as $operator) {
            if (strtolower($operator) === strtolower($query)) {
                $proposals += array_values($this->fields);
            } elseif (self::startsWith($operator, $query)) {
                $proposals[] = self::markDifference($operator, $query);
            } elseif (self::startsWith($query, $operator)) {
                $fieldPart = trim(substr($query, strlen($operator)));
                $proposals = $proposals + $this->getFieldProposals($fieldPart);
            }
        }
        return $proposals;
    }

    /**
     * Return every possible operator of this Filter type
     *
     * @return array    An array
     */
    public function getOperators()
    {
        return $this->operators;
    }

    /**
     * Return true when the given query is valid for this type
     *
     * @param  String $query    The query to test for this filter type
     * @return bool             True if the query can be parsed by this filter type
     */
    public function isValidQuery($query)
    {
        list($field, $operator, $subQuery) = $this->getFieldValueForQuery($query);
        $valid = ($field !== null && $operator !== null);
        if ($valid && $subQuery && $this->subFilter !== null) {
            $valid = $this->subFilter->isValidQuery($subQuery);
        }
        return $valid;
    }

    /**
     * Return a 3 element tupel with array(field, value, right) from the given query part
     *
     * @param  String $query    The query string to use
     * @return array            An 3 element tupel containing the field, value and optionally the right
     *                          side of the query
     */
    public function getFieldValueForQuery($query)
    {
        $operator = $this->getMatchingOperatorForQuery($query);
        if (!$operator) {
            return array(null, null, null);
        }
        $operatorList = array_flip($this->operators);
        $query = trim(substr($query, strlen($operator)));

        $operator = $operatorList[$operator];
        foreach ($this->fields as $key => $field) {
            if (self::startsWith($query, $field)) {
                $subQuery = trim(substr($query, strlen($field)));
                return array($key, $operator === Node::OPERATOR_EQUALS ? 1 : 0, $subQuery);
            }
        }
        return array(null, null, null);
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
        list($field, $value, $subQuery) = $this->getFieldValueForQuery($query);
        if ($field === null || $value === null) {
            return null;
        }
        $node = Node::createOperatorNode(Node::OPERATOR_EQUALS, $field, $value);
        if ($this->subFilter && $subQuery && $this->subFilter->isValidQuery($subQuery)) {
            $subNode = $this->subFilter->createTreeNode($subQuery, $this->timeField);
            $conjunctionNode = Node::createAndNode();
            $conjunctionNode->left = $subNode;
            $conjunctionNode->right = $node;
            $node = $conjunctionNode;
        }
        return $node;
    }
}
