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


namespace Icinga\Module\Monitoring\Filter\Backend;


use Icinga\Filter\Query\Tree;
use Icinga\Filter\Query\Node;
use Icinga\Module\Monitoring\DataView\DataView;


class IdoQueryConverter
{
    private $view;
    private $query;
    private $params = array();

    public function getParams()
    {
        return $this->params;
    }

    public function __construct(DataView $view, array $initialParams = array())
    {
        $this->view = $view;
        $this->query = $this->view->getQuery();
        $this->params = $initialParams;
    }

    private function getSqlOperator($operator)
    {
        switch($operator) {
            case Node::OPERATOR_EQUALS:
                return 'LIKE';
            case Node::OPERATOR_EQUALS_NOT:
                return 'NOT LIKE';
            default:
                return $operator;
        }
    }

    private function nodeToSqlQuery(Node $node)
    {
        if ($node->type !== Node::TYPE_OPERATOR) {
            return $this->parseConjunctionNode($node);
        } else {
            return $this->parseOperatorNode($node);
        }
    }

    private function parseConjunctionNode(Node $node)
    {
        $queryString =  '';
        $leftQuery = $this->nodeToSqlQuery($node->left);
        $rightQuery = $this->nodeToSqlQuery($node->right);
        if ($leftQuery != '') {
            $queryString .= $leftQuery . ' ';
        }
        if ($rightQuery != '') {
            $queryString .= (($queryString !== '') ? $node->type . ' ' : ' ') . $rightQuery;
        }
        return $queryString;
    }

    private function parseOperatorNode(Node $node)
    {
        if (!$this->view->isValidFilterColumn($node->left) && $this->query->getMappedColumn($node->left)) {
            return '';
        }
        $queryString = $this->query->getMappedColumn($node->left);
        $queryString .= ' ' . (is_integer($node->right) ? $node->operator : $this->getSqlOperator($node->operator));
        $queryString .= ' ? ';
        $this->params[] = $this->getParameterValue($node);
        return $queryString;
    }

    private function getParameterValue(Node $node) {

        switch($node->context) {
            case Node::CONTEXT_TIMESTRING:
                return strtotime($node->right);
            default:
                return $node->right;
        }
    }

    public function treeToSql(Tree $tree)
    {
        if ($tree->root == null) {
            return '';
        }
        return $this->nodeToSqlQuery($tree->root);
    }
}