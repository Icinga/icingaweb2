<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
    public function accept()
    {
        $section = $this->current();
        /** @var \Icinga\Module\Doc\DocSection $section */
        if ($section->getChapter()->getId() === $this->chapter) {
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return new static($this->getInnerIterator()->getChildren(), $this->chapter);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
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
