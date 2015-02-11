<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc\Search;

use Countable;
use RecursiveFilterIterator;
use Icinga\Data\Tree\TreeNodeIterator;

/**
 * Iterator over doc sections that match a given search criteria
 *
 * @method TreeNodeIterator getInnerIterator() {
 *     {@inheritdoc}
 * }
 */
class DocSearchIterator extends RecursiveFilterIterator implements Countable
{
    /**
     * Search criteria
     *
     * @type DocSearch
     */
    protected $search;

    /**
     * Search matches
     *
     * @type DocSearchMatch[]|null
     */
    protected $matches;

    /**
     * Create a new iterator over doc sections that match the given search criteria
     *
     * @param TreeNodeIterator  $iterator
     * @param DocSearch         $search
     */
    public function __construct(TreeNodeIterator $iterator, DocSearch $search)
    {
        $this->search = $search;
        parent::__construct($iterator);
    }

    /**
     * Accept sections that match the search
     *
     * @return bool Whether the current element of the iterator is acceptable
     *              through this filter
     */
    public function accept()
    {
        $section = $this->current();
        /** @type $section \Icinga\Module\Doc\DocSection */
        $matches = array();
        if (($match = $this->search->search($section->getTitle())) !== null) {
            $matches[] = $match->setMatchType(DocSearchMatch::MATCH_HEADER);
        }
        foreach ($section->getContent() as $lineno => $line) {
            if (($match = $this->search->search($line)) !== null) {
                $matches[] = $match
                    ->setMatchType(DocSearchMatch::MATCH_CONTENT)
                    ->setLineno($lineno);
            }
        }
        if (! empty($matches)) {
            $this->matches = $matches;
            return $this;
        }
        if ($section->hasChildren()) {
            $this->matches = null;
            return true;
        }
        return false;
    }

    /**
     * Get the search criteria
     *
     * @return DocSearch
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return new static($this->getInnerIterator()->getChildren(), $this->search);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $count = 0;
        foreach ($this as $section) {
            if ($this->getMatches() !== null) {
                ++$count;
            }
        }
        return $count;
    }

    /**
     * Whether the search did not yield any match
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() === 0;
    }

    /**
     * Get matches
     *
     * @return DocSearchMatch[]|null
     */
    public function getMatches()
    {
        return $this->matches;
    }
}
