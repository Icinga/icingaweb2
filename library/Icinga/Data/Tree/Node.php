<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data\Tree;

use Exception;
use RecursiveIteratorIterator;
use RuntimeException;
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

    /**
     * Find the first child node by searching through nodes deeper than the immediate children using a custom function
     *
     * @param   $callback
     *
     * @return  NodeInterface|null
     * @throws  Exception
     */
    public function findNodeBy($callback)
    {
        if (! is_callable($callback)) {
            throw new RuntimeException('Callable expected');
        }
        foreach (new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST) as $node) {
            try {
                $found = call_user_func($callback, $node);
            } catch (Exception $e) {
                // TODO(el): Log exception and return false instead?
                throw $e;
            }
            if ($found) {
                return $node;
            }
        }
        return null;
    }
}
