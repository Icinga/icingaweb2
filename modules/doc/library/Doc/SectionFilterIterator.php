<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}

namespace Icinga\Module\Doc;

use Countable;
use RecursiveFilterIterator;
use Icinga\Data\Tree\NodeInterface;

/**
 * Recursive iterator over sections that are part of a particular chapter
 */
class SectionFilterIterator extends RecursiveFilterIterator implements Countable
{
    /**
     * The chapter title to filter for
     *
     * @var string
     */
    protected $chapterTitle;

    /**
     * Create a new SectionFilterIterator
     *
     * @param NodeInterface $node           Node
     * @param string        $chapterTitle   The chapter title to filter for
     */
    public function __construct(NodeInterface $node, $chapterTitle)
    {
        parent::__construct($node);
        $this->chapterTitle = $chapterTitle;
    }

    /**
     * Accept sections that are part of the given chapter
     *
     * @return bool Whether the current element of the iterator is acceptable
     *              through this filter
     */
    public function accept()
    {
        $section = $this->getInnerIterator()->current()->getValue();
        /* @var $section \Icinga\Module\Doc\Section */
        if ($section->getChapterTitle() === $this->chapterTitle) {
            return true;
        }
        return false;
    }

    /**
     * (non-PHPDoc)
     * @see RecursiveFilterIterator::getChildren()
     */
    public function getChildren()
    {
        return new static($this->getInnerIterator()->getChildren(), $this->chapterTitle);
    }

    /**
     * (non-PHPDoc)
     * @see Countable::count()
     */
    public function count()
    {
        return iterator_count($this);
    }
}
