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

namespace Icinga\Filter\Query;

/**
 * Container class for the Node of a query tree
 */
class Node
{
    const TYPE_AND              = 'AND';
    const TYPE_OR               = 'OR';
    const TYPE_OPERATOR         = 'OPERATOR';

    const OPERATOR_EQUALS       = '=';
    const OPERATOR_EQUALS_NOT   = '!=';
    const OPERATOR_GREATER      = '>';
    const OPERATOR_LESS         = '<';
    const OPERATOR_GREATER_EQ   = '>=';
    const OPERATOR_LESS_EQ      = '<=';

    const CONTEXT_TIMESTRING    = 'timestring';
    /**
     * Array containing all possible operators
     *
     * @var array
     */
    static public $operatorList = array(
        self::OPERATOR_EQUALS, self::OPERATOR_EQUALS_NOT, self::OPERATOR_GREATER,
        self::OPERATOR_LESS, self::OPERATOR_GREATER_EQ, self::OPERATOR_LESS_EQ
    );

    /**
     * The type of this node
     *
     * @var string
     */
    public $type = self::TYPE_OPERATOR;

    /**
     * The operator of this node, if type is TYPE_OPERATOR
     *
     * @var string
     */
    public $operator = '';

    /**
     * The parent of this node or null if no parent exists
     *
     * @var Node
     */
    public $parent;

    /**
     * The left element of this Node
     *
     * @var String|Node
     */
    public $left;

    /**
     * The right element of this Node
     *
     * @var String|Node
     */
    public $right;

    /**
     * Additional information for this node (like that it represents a date)
     *
     * @var mixed
     */
    public $context;

    /**
     * Factory method for creating operator nodes
     *
     * @param String    $operator   The operator to use
     * @param String    $left       The left side of the node, i.e. target (mostly attribute)
     *                              to query for with this node
     * @param String    $right      The right side of the node, i.e. the value to use for querying
     *
     * @return Node                 An operator Node instance
     */
    public static function createOperatorNode($operator, $left, $right)
    {
        $node = new Node();
        $node->type = self::TYPE_OPERATOR;
        $node->operator = $operator;
        $node->left = $left;
        if ($right === null) {
            $right = array();
        } elseif (!is_array($right)) {
            $right = array($right);
        }
        foreach ($right as &$value) {
            $value = trim($value);
        }
        $node->right = $right;
        return $node;
    }

    /**
     * Factory method for creating an AND conjunction node
     *
     * @return Node     An AND Node instance
     */
    public static function createAndNode()
    {
        $node = new Node();
        $node->type = self::TYPE_AND;
        return $node;
    }

    /**
     * Factory method for creating an OR conjunction node
     *
     * @return Node     An OR Node instance
     */
    public static function createOrNode()
    {
        $node = new Node();
        $node->type = self::TYPE_OR;
        return $node;
    }
}
