<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
