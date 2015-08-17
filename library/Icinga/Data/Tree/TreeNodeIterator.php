<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Tree;

use ArrayIterator;
use RecursiveIterator;

/**
 * Iterator over a tree node's children
 */
class TreeNodeIterator implements RecursiveIterator
{
    /**
     * The node's children
     *
     * @var array
     */
    protected $children;

    /**
     * Create a new iterator over a tree node's children
     *
     * @param TreeNode $node
     */
    public function __construct(TreeNode $node)
    {
        $this->children = new ArrayIterator($node->getChildren());
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->children->current();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->children->key();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->children->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->children->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->children->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren()
    {
        return $this->current()->hasChildren();
    }

    /**
     * {@inheritdoc}
     * @return TreeNodeIterator
     */
    public function getChildren()
    {
        return new static($this->current());
    }
}
