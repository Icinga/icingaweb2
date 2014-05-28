<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data\Tree;

interface NodeInterface
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

    /**
     * Whether the node has children
     *
     * @return bool
     */
    public function hasChildren();

    /**
     * Get the node's children
     *
     * @return array
     */
    public function getChildren();
}
