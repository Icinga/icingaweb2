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


namespace Icinga\Filter\Query;

/**
 * A binary tree representing queries in an interchangeable way
 *
 * This tree should always be created from queries and used to create queries
 * or convert query formats. Currently it doesn't support grouped expressions,
 * although this can be implemented rather easily in the tree (the problem is more or less
 * how to implement it in query languages)
 */
class Tree
{
    /**
     * The curretnt root node of the Tree
     *
     * @var Node|null
     */
    public $root;

    /**
     * The last inserted node of the Tree
     *
     * @var Node|null
     */
    private $lastNode;

    /**
     * Insert a node into this tree, recognizing type and insert position
     *
     * @param Node $node        The node to insert into the tree
     */
    public function insert(Node $node)
    {

        if ($this->root === null) {
            $this->root = $node;
        } else {
            switch ($node->type) {
                case Node::TYPE_AND:
                    $this->insertAndNode($node, $this->root);
                    break;
                case Node::TYPE_OR:
                    $this->insertOrNode($node, $this->root);
                    break;
                case Node::TYPE_OPERATOR:
                    $node->parent = $this->lastNode;
                    if ($this->lastNode->left == null) {
                        $this->lastNode->left = $node;
                    } else if($this->lastNode->right == null) {
                        $this->lastNode->right = $node;
                    }
                    break;
            }
        }
        $this->lastNode = $node;
    }

    /**
     * Determine the insert position of an AND node, using $currentNode as the parent node
     * and insert the tree
     *
     * And nodes are always with a higher priority than other nodes and only traverse down the tree
     * when encountering another AND tree on the way
     *
     * @param Node $node            The node to insert
     * @param Node $currentNode     The current node context
     */
    private function insertAndNode(Node $node, Node $currentNode)
    {

        if ($currentNode->type != Node::TYPE_AND) {
            // No AND node, insert into tree
            if($currentNode->parent !== null) {
                $node->parent = $currentNode->parent;
                if ($currentNode->parent->left === $currentNode) {
                    $currentNode->parent->left = $node;
                } else {
                    $currentNode->parent->right = $node;
                }
            } else {
                $this->root = $node;
            }
            $currentNode->parent = $node;
            if ($node->left) {
                $currentNode->right = $node->left;
            }
            $node->left = $currentNode;
            $node->parent = null;
            return;

        } elseif ($currentNode->left == null) {
            // Insert right if there's place
            $currentNode->left = $node;
            $node->parent = $currentNode;
        } elseif ($currentNode->right == null) {
            // Insert right if there's place
            $currentNode->right = $node;
            $node->parent = $currentNode;
        } else {
            // traverse down the tree if free insertion point is found
            $this->insertAndNode($node, $currentNode->right);

        }
    }

    /**
     * Insert an OR node
     *
     * OR nodes are always inserted over operator nodes but below AND nodes
     *
     * @param Node $node            The OR node to insert
     * @param Node $currentNode     The current context to use for insertion
     */
    private function insertOrNode(Node $node, Node $currentNode)
    {
        if ($currentNode->type === Node::TYPE_OPERATOR) {
            // Always insert when encountering an operator node
            if($currentNode->parent !== null) {
                $node->parent = $currentNode->parent;
                if ($currentNode->parent->left === $currentNode) {
                    $currentNode->parent->left = $node;
                } else {
                    $currentNode->parent->right = $node;
                }
            } else {
                $this->root = $node;
            }
            $currentNode->parent = $node;
            $node->left = $currentNode;
        } elseif ($currentNode->left === null) {
            $currentNode->left = $node;
            $node->parent = $currentNode;
            return;
        } elseif ($currentNode->right === null) {
            $currentNode->right = $node;
            $node->parent = $currentNode;
            return;
        } else {
            $this->insertOrNode($node, $currentNode->right);
        }
    }
}
