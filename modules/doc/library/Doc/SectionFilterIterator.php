<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
     * The chapter ID to filter for
     *
     * @var string
     */
    protected $chapterId;

    /**
     * Create a new SectionFilterIterator
     *
     * @param NodeInterface $node           Node
     * @param string        $chapterId      The chapter ID to filter for
     */
    public function __construct(NodeInterface $node, $chapterId)
    {
        parent::__construct($node);
        $this->chapterId = $chapterId;
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
        if ($section->getChapterId() === $this->chapterId) {
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
        return new static($this->getInnerIterator()->getChildren(), $this->chapterId);
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
