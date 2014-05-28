<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Doc;

use IteratorAggregate;
use Icinga\Data\Tree\NodeInterface;
use Icinga\Data\Tree\TreeIterator;

class DocToc implements NodeInterface, IteratorAggregate
{
    protected $children = array();

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
        $child = new self($value);
        $this->children[] = $child;
        return $child;
    }

    public function hasChildren()
    {
        return ! empty($this->children);
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getIterator()
    {
        return new TreeIterator($this);
    }
}
