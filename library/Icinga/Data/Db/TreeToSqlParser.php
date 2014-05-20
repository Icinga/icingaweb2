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

namespace Icinga\Data\Db;

use Icinga\Data\BaseQuery;
use Icinga\Filter\Query\Tree;
use Icinga\Filter\Query\Node;

/**
 * Converter class that takes a query tree and creates an SQL Query from it's state
 */
class TreeToSqlParser
{
    /**
     * The query class to use as the base for converting
     *
     * @var BaseQuery
     */
    private $query;

    /**
     * The type of the filter (WHERE or HAVING, depending whether it's an aggregate query)
     * @var string
     */
    private $type = 'WHERE';

    /**
     * Create a new converter from this query
     *
     * @param   BaseQuery   $query      The query to use for conversion
     */
    public function __construct(BaseQuery $query)
    {
        $this->query = $query;
    }

    /**
     * Return the SQL equivalent fo the given text operator
     *
     * @param  String $operator     The operator from the query node
     * @return string               The operator for the sql query part
     */
    private function getSqlOperator($operator, array $right)
    {

        switch($operator) {
            case Node::OPERATOR_EQUALS:
                if (count($right) > 1) {
                    return 'IN';
                } else {
                    foreach ($right as $r) {
                        if (strpos($r, '*') !== false) {
                            return 'LIKE';
                        }
                    }
                    return '=';
                }
            case Node::OPERATOR_EQUALS_NOT:
                if (count($right) > 1) {
                    return 'NOT IN';
                } else {
                    return 'NOT LIKE';
                }
            default:
                return $operator;
        }
    }

    /**
     * Convert a Query Tree node to an sql string
     *
     * @param Node $node    The node to convert
     * @return string       The sql string representing the node's state
     */
    private function nodeToSqlQuery(Node $node)
    {
        if ($node->type !== Node::TYPE_OPERATOR) {
            return $this->parseConjunctionNode($node);
        } else {
            return $this->parseOperatorNode($node);
        }
    }

    /**
     * Parse an AND or OR node to an sql string
     *
     * @param Node $node        The AND/OR node to parse
     * @return string           The sql string representing this node
     */
    private function parseConjunctionNode(Node $node)
    {
        $queryString =  '';
        $leftQuery = $node->left !== null ? $this->nodeToSqlQuery($node->left) : '';
        $rightQuery = $node->right !== null ? $this->nodeToSqlQuery($node->right) : '';

        if ($leftQuery != '') {
            $queryString .= $leftQuery . ' ';
        }

        if ($rightQuery != '') {
            $queryString .= (($queryString !== '') ? $node->type . ' ' : ' ') . $rightQuery;
        }
        return $queryString;
    }

    /**
     * Parse an operator node to an sql string
     *
     * @param Node $node        The operator node to parse
     * @return string           The sql string representing this node
     */
    private function parseOperatorNode(Node $node)
    {
        if (!$this->query->isValidFilterTarget($node->left) && $this->query->getMappedField($node->left)) {
            return '';
        }

        $this->query->requireColumn($node->left);
        $queryString = '(' . $this->query->getMappedField($node->left) . ')';

        if ($this->query->isAggregateColumn($node->left)) {
            $this->type = 'HAVING';
        }
        $queryString .= ' ' . (is_integer($node->right) ?
                $node->operator : $this->getSqlOperator($node->operator, $node->right)) . ' ';
        $queryString = $this->addValueToQuery($node, $queryString);
        return $queryString;
    }

    /**
     * Convert a node value to it's sql equivalent
     *
     * This currently only detects if the node is in the timestring context and calls strtotime if so and it replaces
     * '*' with '%'
     *
     * @param Node $node                The node to retrieve the sql string value from
     * @return String|int               The converted and quoted value
     */
    private function addValueToQuery(Node $node, $query) {
        $values = array();

        foreach ($node->right as $value)  {
            if ($node->operator === Node::OPERATOR_EQUALS || $node->operator === Node::OPERATOR_EQUALS_NOT) {
                $value = str_replace('*', '%', $value);
            }
            if ($this->query->isTimestamp($node->left)) {
                $node->context = Node::CONTEXT_TIMESTRING;
            }
            if ($node->context === Node::CONTEXT_TIMESTRING && !is_numeric($value)) {
                $value = strtotime($value);
            }
            if (preg_match('/^\d+$/', $value)) {
                $values[] = $value;
            } else {
                $values[] = $this->query->getDatasource()->getConnection()->quote($value);
            }
        }
        $valueString = join(',', $values);

        if (count($values) > 1) {
            return $query . '( '. $valueString . ')';
        }
        return $query . $valueString;
    }

    /**
     * Apply the given tree to the query, either as where or as having clause
     *
     * @param Tree $tree                        The tree representing the filter
     * @param \Zend_Db_Select $baseQuery        The query to apply the filter on
     */
    public function treeToSql(Tree $tree, $baseQuery)
    {
        if ($tree->root == null) {
            return;
        }
        $sql = $this->nodeToSqlQuery($tree->normalizeTree($tree->root));

        if ($this->filtersAggregate()) {
            $baseQuery->having($sql);
        } else {
            $baseQuery->where($sql);
        }
    }

    /**
     * Return true if this is an filter that should be applied after aggregation
     *
     * @return bool         True when having should be used, otherwise false
     */
    private function filtersAggregate()
    {
        return $this->type === 'HAVING';
    }
}
