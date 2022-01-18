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

    public function current(): ?DOMNode
    {
        return $this->children->current();
    }

    public function key(): int
    {
        return $this->children->key();
    }

    public function next(): void
    {
        $this->children->next();
    }

    public function rewind(): void
    {
        $this->children->rewind();
    }

    public function valid(): bool
    {
        return $this->children->valid();
    }

    public function hasChildren(): bool
    {
        return $this->current()->hasChildNodes();
    }

    public function getChildren(): DomNodeIterator
    {
        return new static($this->current());
    }
}
