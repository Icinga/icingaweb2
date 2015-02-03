<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Data\Tree;

use SplDoublyLinkedList;

class Node extends SplDoublyLinkedList implements NodeInterface
{
    /**
     * The node's value
     *
     * @var mixed
     */
    protected $value;

    /**
     * Create a new node
     *
     * @param mixed $value The node's value
     */
    public function __construct($value = null)
    {
        $this->value = $value;
    }

    /**
     * Get the node's value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Create a new node from the given value and insert the node as the last child of this node
     *
     * @param   mixed           $value  The node's value
     *
     * @return  NodeInterface           The appended node
     */
    public function appendChild($value)
    {
        $child = new static($value);
        $this->push($child);
        return $child;
    }

    /**
     * Whether this node has child nodes
     *
     * @return bool
     */
    public function hasChildren()
    {
        $current = $this->current();
        if ($current === null) {
            $current = $this;
        }
        return ! $current->isEmpty();
    }

    /**
     * Get the node's child nodes
     *
     * @return NodeInterface
     */
    public function getChildren()
    {
        $current = $this->current();
        if ($current === null) {
            $current = $this;
        }
        return $current;
    }
}
