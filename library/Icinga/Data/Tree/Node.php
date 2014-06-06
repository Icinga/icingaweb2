<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data\Tree;

use RecursiveIteratorIterator;
use RuntimeException;
use SplDoublyLinkedList;

class Node extends SplDoublyLinkedList implements NodeInterface
{
    protected $value;

    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function appendChild($value)
    {
        $child = new static($value);
        $this->push($child);
        return $child;
    }

    public function hasChildren()
    {
        $current = $this->current();
        if ($current === null) {;
            $current = $this;
        }
        return ! $current->isEmpty();
    }

    public function getChildren()
    {
        $current = $this->current();
        if ($current === null) {;
            $current = $this;
        }
        return $current;
    }

    public function findNodeBy($callback)
    {
        if (! is_callable($callback)) {
            throw new RuntimeException();
        }
        foreach (new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST) as $node) {
            if (call_user_func($callback, $node)) {
                return $node;
            }
        }
        return null;
    }
}
