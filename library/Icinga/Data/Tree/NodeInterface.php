<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data\Tree;

use RecursiveIterator;

interface NodeInterface extends RecursiveIterator
{
    /**
     * Append a child to the node
     *
     * @param   mixed $value
     *
     * @return  self
     */
    public function appendChild($value);

    /**
     * Get the node's value
     *
     * @return mixed
     */
    public function getValue();
}
