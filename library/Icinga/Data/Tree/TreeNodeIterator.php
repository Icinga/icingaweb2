<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

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
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->children->current();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->children->key();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->children->next();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->children->rewind();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->children->valid();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function hasChildren()
    {
        return $this->current()->hasChildren();
    }

    /**
     * {@inheritdoc}
     * @return TreeNodeIterator
     */
    #[\ReturnTypeWillChange]
    public function getChildren()
    {
        return new static($this->current());
    }

    /**
     * Get whether the iterator is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return ! $this->children->count();
    }
}
