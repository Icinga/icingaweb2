<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data\Tree;

use RecursiveIterator;

interface NodeInterface extends RecursiveIterator
{
    /**
     * Create a new node from the given value and insert the node as the last child of this node
     *
     * @param   mixed           $value  The node's value
     *
     * @return  NodeInterface           The appended node
     */
    public function appendChild($value);

    /**
     * Get the node's value
     *
     * @return mixed
     */
    public function getValue();
}
