<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Tree;

use Identifiable;
use IteratorAggregate;

class Node implements Identifiable, IteratorAggregate
{
    /**
     * The node's ID
     *
     * @type mixed
     */
    protected $id;

    /**
     * The node's value
     *
     * @var mixed
     */
    protected $value;

    /**
     * The node's children
     *
     * @type array
     */
    protected $children = array();

    /**
     * Set the node's ID
     *
     * @param   mixed   $id ID of the node
     *
     * @return  $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see Identifiable::getId() For the method documentation.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the node's value
     *
     * @param   mixed   $value
     *
     * @return  $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
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
     * Append a child node as the last child of this node
     *
     * @param   Node    $child  The child to append
     *
     * @return  $this
     */
    public function appendChild(Node $child)
    {
        $this->children[] = $child;
        return $this;
    }


    /**
     * Get whether the node has children
     *
     * @return bool
     */
    public function hasChildren()
    {
        return ! empty($this->children);
    }

    /**
     * Get the node's children
     *
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * (non-PHPDoc)
     * @see IteratorAggregate::getIterator() For the method documentation.
     */
    public function getIterator()
    {
        return new TreeNodeIterator($this);
    }
}
