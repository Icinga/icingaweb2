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

use Icinga\Filter\Filterable;

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

    public function insertTree(Tree $tree)
    {
        $this->insertSubTree($tree->root);
        $this->root = $this->normalizeTree($this->root);
    }

    private function insertSubTree(Node $node)
    {
        if ($node->type === Node::TYPE_OPERATOR) {
            $this->insert($node);
        } else {
            $this->insert($node->type === Node::TYPE_AND ? Node::createAndNode() : Node::createOrNode());
            $this->insertSubTree($node->left);
            $this->insertSubTree($node->right);
        }
    }

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
                    if ($this->lastNode->type == Node::TYPE_OPERATOR) {
                        $this->insert(Node::createAndNode());
                    }
                    $node->parent = $this->lastNode;
                    if ($this->lastNode->left == null) {
                        $this->lastNode->left = $node;
                    } elseif ($this->lastNode->right == null) {
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
            if ($currentNode->parent !== null) {
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
            if ($currentNode->parent !== null) {
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

    /**
     * Return a copy of this tree that only contains filters that can be applied for the given Filterable
     *
     * @param Filterable $filter        The Filterable to test element nodes agains
     * @return Tree                     A copy of this tree that only contains nodes for the given filter
     */
    public function getCopyForFilterable(Filterable $filter)
    {
        $copy = $this->createCopy();
        if (!$this->root) {
            return $copy;
        }

        $copy->root = $this->removeInvalidFilter($copy->root, $filter);
        return $copy;
    }

    /**
     * Remove all tree nodes that are not applicable ot the given Filterable
     *
     * @param Node $node                The root node to use
     * @param Filterable $filter        The Filterable to test nodes against
     * @return Node                     The normalized tree node
     */
    public function removeInvalidFilter($node, Filterable $filter)
    {
        if ($node === null) {
            return $node;
        }
        if ($node->type === Node::TYPE_OPERATOR) {
            if (!$filter->isValidFilterTarget($node->left)) {
                return null;
            } else {
                return $node;
            }
        }

        $node->left = $this->removeInvalidFilter($node->left, $filter);
        $node->right = $this->removeInvalidFilter($node->right, $filter);

        if ($node->left || $node->right) {
            if (!$node->left) {
                $node->left = $node->right;
                $node->right = null;
            }
            return $node;
        }

        return null;
    }

    /**
     * Normalize this tree and fix incomplete nodes
     *
     * @param  Node $node       The root node to normalize
     * @return Node             The normalized root node
     */
    public static function normalizeTree($node)
    {

        if ($node->type === Node::TYPE_OPERATOR) {
            return $node;
        }
        if ($node === null) {
            return null;
        }
        if ($node->left && $node->right) {
            $node->left =  self::normalizeTree($node->left);
            $node->right = self::normalizeTree($node->right);
            return $node;
        } elseif ($node->left) {
            return $node->left;
        } elseif ($node->right) {
            return $node->right;
        }

    }

    /**
     * Return an array of all attributes in this tree
     *
     * @param Node $ctx     The root node to use instead of the tree root
     * @return array        An array of attribute names
     */
    public function getAttributes($ctx = null)
    {
        $result = array();
        $ctx = $ctx ? $ctx : $this->root;
        if ($ctx == null) {
            return $result;
        }
        if ($ctx->type === Node::TYPE_OPERATOR) {
            $result[] = $ctx->left;
        } else {
            $result = $result + $this->getAttributes($ctx->left) + $this->getAttributes($ctx->right);
        }
        return $result;
    }

    /**
     * Create a copy of this tree without the given node
     *
     * @param Node $node        The node to remove
     * @return Tree             A copy of the given tree
     */
    public function withoutNode(Node $node)
    {
        $tree = $this->createCopy();
        $toRemove = $tree->findNode($node);
        if ($toRemove !== null) {
            if ($toRemove === $tree->root) {
                $tree->root = null;
                return $tree;
            }
            if ($toRemove->parent->left === $toRemove) {
                $toRemove->parent->left = null;
            } else {
                $toRemove->parent->right = null;
            }
        }
        $tree->root = $tree->normalizeTree($tree->root);
        return $tree;
    }

    /**
     * Create an independent copy of this tree
     *
     * @return Tree     A copy of this tree
     */
    public function createCopy()
    {
        $tree = new Tree();
        if ($this->root === null) {
            return $tree;
        }

        $this->copyBranch($this->root, $tree);
        return $tree;
    }

    /**
     * Copy the given node or branch into the given tree
     *
     * @param Node $node        The node to copy
     * @param Tree $tree        The tree to insert the copied node and it's subnodes to
     */
    private function copyBranch(Node $node, Tree &$tree)
    {
        if ($node->type === Node::TYPE_OPERATOR) {
            $copy = Node::createOperatorNode($node->operator, $node->left, $node->right);
            $copy->context = $node->context;
            $tree->insert($copy);
        } else {
            if ($node->left) {
                $this->copyBranch($node->left, $tree);
            }
            $tree->insert($node->type === Node::TYPE_OR ? Node::createOrNode() : Node::createAndNode());
            if ($node->right) {
                $this->copyBranch($node->right, $tree);
            }
        }
    }

    /**
     * Look for a given node in the tree and return it if exists
     *
     * @param Node $node        The node to look for
     * @param Node $ctx         The node to use as the root of  the tree
     *
     * @return Node             The node that matches $node in the tree or null
     */
    public function findNode(Node $node, $ctx = null)
    {
        $ctx = $ctx ? $ctx : $this->root;
        if ($ctx === null) {
            return null;
        }

        if ($ctx->type == Node::TYPE_OPERATOR) {
            if ($ctx->left == $node->left  && $ctx->operator == $node->operator) {
                if (empty($node->right) || $ctx->right == $node->right) {
                    return $ctx;
                }
            }
            return null;
        } else {
            $result = null;
            if ($ctx->left) {
                $result = $this->findNode($node, $ctx->left);
            } if ($result == null && $ctx->right) {
                $result = $this->findNode($node, $ctx->right);

            }

            return $result;
        }
    }

    /**
     * Return true if A node with the given attribute on the left side exists
     *
     * @param String $name          The attribute to test for existence
     * @param Node   $ctx           The current root node
     * @oaram bool   $isRecursive   Internal flag to disable null nodes being replaced with the tree root
     *
     * @return bool                 True if a node contains $name on the left side, otherwise false
     */
    public function hasNodeWithAttribute($name, $ctx = null, $isRecursive = false)
    {
        if (!$isRecursive) {
            $ctx = $ctx ? $ctx : $this->root;
        }
        if ($ctx === null) {
            return false;
        }
        if ($ctx->type === Node::TYPE_OPERATOR) {
            return $ctx->left === $name;
        } else {
            return $this->hasNodeWithAttribute($name, $ctx->left, true)
                    || $this->hasNodeWithAttribute($name, $ctx->right, true);
        }
    }
}
