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
     * @type array
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
     * (non-PHPDoc)
     * @see \RecursiveIterator::current() For the method documentation.
     */
    public function current()
    {
        return $this->children->current();
    }

    /**
     * (non-PHPDoc)
     * @see \RecursiveIterator::key() For the method documentation.
     */
    public function key()
    {
        return $this->children->key();
    }

    /**
     * (non-PHPDoc)
     * @see \RecursiveIterator::next() For the method documentation.
     */
    public function next()
    {
        $this->children->next();
    }

    /**
     * (non-PHPDoc)
     * @see \RecursiveIterator::rewind() For the method documentation.
     */
    public function rewind()
    {
        $this->children->rewind();
    }

    /**
     * (non-PHPDoc)
     * @see \RecursiveIterator::valid() For the method documentation.
     */
    public function valid()
    {
        return $this->children->valid();
    }

    /**
     * (non-PHPDoc)
     * @see \RecursiveIterator::hasChildren() For the method documentation.
     */
    public function hasChildren()
    {
        return $this->current()->hasChildren();
    }

    /**
     * (non-PHPDoc)
     * @see \RecursiveIterator::getChildren() For the method documentation.
     */
    public function getChildren()
    {
        return new static($this->current());
    }
}
