<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Dom;

use DOMNode;
use IteratorIterator;
use RecursiveIterator;

/**
 * Recursive iterator over a DOMNode
 *
 * Usage example:
 * <code>
 * <?php
 *
 * namespace Icinga\Example;
 *
 * use DOMDocument;
 * use RecursiveIteratorIterator;
 * use Icinga\Web\Dom\DomIterator;
 *
 * $doc = new DOMDocument();
 * $doc->loadHTML(...);
 * $dom = new RecursiveIteratorIterator(new DomNodeIterator($doc), RecursiveIteratorIterator::SELF_FIRST);
 * foreach ($dom as $node) {
 *     ....
 * }
 * </code>
 */
class DomNodeIterator implements RecursiveIterator
{
    /**
     * The node's children
     *
     * @var IteratorIterator
     */
    protected $children;

    /**
     * Create a new iterator over a DOMNode's children
     *
     * @param DOMNode $node
     */
    public function __construct(DOMNode $node)
    {
        $this->children = new IteratorIterator($node->childNodes);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->children->current();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->children->key();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->children->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->children->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->children->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren()
    {
        return $this->current()->hasChildNodes();
    }

    /**
     * {@inheritdoc}
     * @return DomNodeIterator
     */
    public function getChildren()
    {
        return new static($this->current());
    }
}
