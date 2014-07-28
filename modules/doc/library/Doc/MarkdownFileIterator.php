<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Doc;

use RecursiveFilterIterator;

/**
 * Recursive iterator over Markdown files
 */
class MarkdownFileIterator extends RecursiveFilterIterator
{
    /**
     * Accept files with '.md' suffix
     *
     * @return bool Whether the current element of the iterator is acceptable
     *              through this filter
     */
    public function accept()
    {
        $current = $this->getInnerIterator()->current();
        /* @var $current \SplFileInfo */
        if (! $current->isFile()) {
            return false;
        }
        $filename = $current->getFilename();
        $sfx = substr($filename, -3);
        return $sfx === false ? false : strtolower($sfx) === '.md';
    }
}
