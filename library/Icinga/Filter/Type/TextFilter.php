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

class TextFilter extends FilterType
{
    /**
     * Mapping of possible text tokens to normalized operators
     *
     * @var array
     */
    private $operators = array(
        'Is'            => Node::OPERATOR_EQUALS,
        'Is Not'        => Node::OPERATOR_EQUALS_NOT,
        'Starts With'   => Node::OPERATOR_EQUALS,
        'Ends With'     => Node::OPERATOR_EQUALS,
        'Contains'      => Node::OPERATOR_EQUALS,
        '='             => Node::OPERATOR_EQUALS,
        '!='            => Node::OPERATOR_EQUALS_NOT,
        'Like'          => Node::OPERATOR_EQUALS,
        'Matches'       => Node::OPERATOR_EQUALS
    );

    /**
     * Return all possible operator tokens for this filter
     *
     * @return array
     */
    public function getOperators()
    {
        return array_keys($this->operators);
    }

    /**
     * Return proposals for the given query part
     *
     * @param String $query     The part of the query that this specifier should parse
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
                $proposals += array('\'' . $this->getProposalsForValues($operator) . '\'');
            } elseif (self::startsWith($operator, $query)) {
                $proposals[] = self::markDifference($operator, $query);
            }
        }

        return $proposals;
    }

    /**
     * Return a (operator, value) tupel representing the given query or (null, null) if
     * the input is not valid
     *
     * @param String $query     The query part to extract the operator and value from
     * @return array            An array containg the operator as the first item and the value as the second
     *                          or (null, null) if parsing is not possible for this query
     */
    public function getOperatorAndValueFromQuery($query)
    {
        $matchingOperator = $this->getMatchingOperatorForQuery($query);

        if (!$matchingOperator) {
            return array(null, null);
        }
        $valuePart = trim(substr($query, strlen($matchingOperator)));
        if ($valuePart == '') {
            return array($matchingOperator, null);
        }
        $this->normalizeQuery($matchingOperator, $valuePart);
        return array($matchingOperator, $valuePart);
    }

    /**
     * Return true when the given query is valid for this type
     *
     * @param  String $query    The query to test for this filter type
     * @return bool             True if the query can be parsed by this filter type
     */
    public function isValidQuery($query)
    {
        list ($operator, $value) = $this->getOperatorAndValueFromQuery($query);
        return $operator !== null && $value !== null;
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
        list ($operator, $value) = $this->getOperatorAndValueFromQuery($query);
        if ($operator === null || $value === null) {
            return null;
        }
        $node = new Node();
        $node->type = Node::TYPE_OPERATOR;
        $node->operator = $operator;
        $node->left = $leftOperand;
        $node->right = $value;
        return $node;
    }

    /**
     * Normalize the operator and value for the given query
     *
     * This removes quotes and adds wildcards for specific operators.
     * The operator and value will be modified in this method and can be
     * added to a QueryNode afterwards
     *
     * @param String $operator  A reference to the operator string
     * @param String $value     A reference to the value string
     */
    private function normalizeQuery(&$operator, &$value)
    {
        $value = trim($value);

        if ($value[0] == '\'' || $value[0] == '"') {
            $value = substr($value, 1);
        }
        $lastPos = strlen($value) - 1;
        if ($value[$lastPos] == '"' || $value[$lastPos] == '\'') {
            $value = substr($value, 0, -1);
        }

        switch (strtolower($operator)) {
            case 'ends with':
                $value = '*' . $value;
                break;
            case 'starts with':
                $value = $value . '*';
                break;
            case 'matches':
            case 'contains':
                $value = '*' . $value . '*';
                break;
        }
        foreach ($this->operators as $operatorType => $type) {
            if (strtolower($operatorType) === strtolower($operator)) {
                $operator = $type;
            }
        }
    }

    /**
     * Return generic value proposals for the given operator
     *
     * @param String $operator      The operator string to create a proposal for
     * @return string               The created proposals
     */
    public function getProposalsForValues($operator)
    {
        switch (strtolower($operator)) {
            case 'starts with':
                return 'value...';
            case 'ends with':
                return '...value';
            case 'is':
            case 'is not':
            case '=':
            case '!=':
                return 'value';
            case 'matches':
            case 'contains':
            case 'like':
                return '...value...';
        }
    }
}
