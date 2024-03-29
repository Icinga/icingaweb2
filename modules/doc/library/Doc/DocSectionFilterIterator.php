<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc;

use Countable;
use RecursiveFilterIterator;
use Icinga\Data\Tree\TreeNodeIterator;

/**
 * Recursive filter iterator over sections that are part of a particular chapter
 *
 * @method TreeNodeIterator getInnerIterator() {
 *     {@inheritdoc}
 * }
 */
class DocSectionFilterIterator extends RecursiveFilterIterator implements Countable
{
    /**
     * Chapter to filter for
     *
     * @var string
     */
    protected $chapter;

    /**
     * Create a new recursive filter iterator over sections that are part of a particular chapter
     *
     * @param TreeNodeIterator  $iterator
     * @param string            $chapter      The chapter to filter for
     */
    public function __construct(TreeNodeIterator $iterator, $chapter)
    {
        parent::__construct($iterator);
        $this->chapter = $chapter;
    }

    /**
     * Accept sections that are part of the given chapter
     *
     * @return bool Whether the current element of the iterator is acceptable
     *              through this filter
     */
    public function accept(): bool
    {
        $section = $this->current();
        /** @var \Icinga\Module\Doc\DocSection $section */
        if ($section->getChapter()->getId() === $this->chapter) {
            return true;
        }
        return false;
    }

    public function getChildren(): self
    {
        return new static($this->getInnerIterator()->getChildren(), $this->chapter);
    }

    public function count(): int
    {
        return iterator_count($this);
    }

    /**
     * Whether the filter swallowed every section
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() === 0;
    }
}
