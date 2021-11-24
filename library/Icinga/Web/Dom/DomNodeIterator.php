<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

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
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->children->current();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->children->key();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->children->next();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->children->rewind();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->children->valid();
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function hasChildren()
    {
        return $this->current()->hasChildNodes();
    }

    /**
     * {@inheritdoc}
     * @return DomNodeIterator
     */
    #[\ReturnTypeWillChange]
    public function getChildren()
    {
        return new static($this->current());
    }
}
