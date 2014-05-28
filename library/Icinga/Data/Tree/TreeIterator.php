<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data\Tree;

//use RecursiveIterator;
//
//class TreeIterator implements RecursiveIterator
//{
//    protected $position = 0;
//
//    protected $nodes;
//
//    public function __construct(NodeInterface $node)
//    {
//        $this->nodes = $node->getChildren();
//    }
//
//    public function hasChildren()
//    {
//        return $this->current()->hasChildren();
//    }
//
//    public function getChildren()
//    {
//        return new self($this->current());
//    }
//
//    public function current()
//    {
//        return $this->nodes[$this->position];
//    }
//
//    public function next()
//    {
//        ++$this->position;
//    }
//
//    public function valid()
//    {
//        return isset($this->nodes[$this->position]);
//    }
//
//    public function rewind()
//    {
//        $this->position = 0;
//    }
//
//    public function key()
//    {
//        return $this->position;
//    }
//}

use ArrayIterator;
use RecursiveIterator;

class TreeIterator extends ArrayIterator implements RecursiveIterator
{
    /**
     * Create a new TreeIterator
     *
     * @param NodeInterface $node
     */
    public function __construct(NodeInterface $node)
    {
        parent::__construct($node->getChildren());
    }

    /**
     * Whether an iterator can be created for the current node
     *
     * @return bool
     */
    public function hasChildren()
    {
        return $this->current()->hasChildren();
    }

    /**
     * Return an iterator for the current node
     *
     * @return self
     */
    public function getChildren()
    {
        return new self($this->current());
    }
}
