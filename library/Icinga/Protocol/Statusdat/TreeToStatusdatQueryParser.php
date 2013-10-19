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


namespace Icinga\Protocol\Statusdat;


use Icinga\Filter\Query\Node;
use Icinga\Filter\Query\Tree;
use Icinga\Protocol\Statusdat\Query\Expression;
use Icinga\Protocol\Statusdat\Query\Group;

class TreeToStatusdatQueryParser
{

    private function nodeToQuery(Node $node)
    {
        if ($node->type === Node::TYPE_OPERATOR) {
            $op = $node->operator;
            $value = $node->right;
            if (stripos($node->right, '*') !== false) {
                $op = 'LIKE';
                $value = str_replace('*', '%', $value);
            }

            return new Expression($node->left . ' ' . $op . ' ?', $value);
        } else {
            $group = new Group();
            $group->setType(($node->type === Node::TYPE_OR) ? Group::TYPE_OR  : Group::TYPE_AND);
            $group->addItem($this->nodeToQuery($node->left));
            $group->addItem($this->nodeToQuery($node->right));
            return $group;
        }
    }


    public function treeToQuery(Tree $tree)
    {

        if ($tree->root !== null) {
            $tree->root = $tree->normalizeTree($tree->root);
            return $this->nodeToQuery($tree->root);
        }
        return null;
    }
}