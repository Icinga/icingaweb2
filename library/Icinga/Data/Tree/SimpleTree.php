<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Tree;

use IteratorAggregate;
use LogicException;

/**
 * A simple tree
 */
class SimpleTree implements IteratorAggregate
{
    /**
     * Root node
     *
     * @var TreeNode
     */
    protected $sentinel;

    /**
     * Nodes
     *
     * @var array
     */
    protected $nodes = array();

    /**
     * Create a new simple tree
     */
    public function __construct()
    {
        $this->sentinel = new TreeNode();
    }

    /**
     * Add a child node
     *
     * @param   TreeNode $child
     * @param   TreeNode $parent
     *
     * @return $this
     */
    public function addChild(TreeNode $child, TreeNode $parent = null)
    {
        if ($parent === null) {
            $parent = $this->sentinel;
        } elseif (! isset($this->nodes[$parent->getId()])) {
            throw new LogicException(sprintf(
                'Can\'t append child node %s to parent node %s: Parent node does not exist',
                $child->getId(),
                $parent->getId()
            ));
        }
        if (isset($this->nodes[$child->getId()])) {
            throw new LogicException(sprintf(
                'Can\'t append child node %s to parent node %s: Child node does already exist',
                $child->getId(),
                $parent->getId()
            ));
        }
        $this->nodes[$child->getId()] = $child;
        $parent->appendChild($child);
        return $this;
    }

    /**
     * Get a node by its ID
     *
     * @param   mixed   $id
     *
     * @return  TreeNode|null
     */
    public function getNode($id)
    {
        if (! isset($this->nodes[$id])) {
            return null;
        }
        return $this->nodes[$id];
    }

    /**
     * {@inheritdoc}
     * @return TreeNodeIterator
     */
    public function getIterator()
    {
        return new TreeNodeIterator($this->sentinel);
    }
}
