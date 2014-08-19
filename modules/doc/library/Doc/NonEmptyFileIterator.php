<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}

namespace Icinga\Module\Doc;

use RecursiveFilterIterator;

/**
 * Recursive iterator over non-empty files
 */
class NonEmptyFileIterator extends RecursiveFilterIterator
{
    /**
     * Accept non-empty files
     *
     * @return bool Whether the current element of the iterator is acceptable
     *              through this filter
     */
    public function accept()
    {
        $current = $this->getInnerIterator()->current();
        /* @var $current \SplFileInfo */
        if (! $current->isFile()
            || $current->getSize() === 0
        ) {
            return false;
        }
        return true;
    }
}
