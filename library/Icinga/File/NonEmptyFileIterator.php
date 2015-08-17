<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\File;

use FilterIterator;

/**
 * Iterator over non-empty files
 *
 * Usage example:
 * <code>
 * <?php
 *
 * namespace Icinga\Example;
 *
 * use RecursiveDirectoryIterator;
 * use RecursiveIteratorIterator;
 * use Icinga\File\NonEmptyFilterIterator;
 *
 * $nonEmptyFiles = new NonEmptyFileIterator(
 *     new RecursiveIteratorIterator(
 *         new RecursiveDirectoryIterator(__DIR__),
 *         RecursiveIteratorIterator::SELF_FIRST
 *     )
 * );
 * </code>
 */
class NonEmptyFileIterator extends FilterIterator
{
    /**
     * Accept non-empty files
     *
     * @return bool Whether the current element of the iterator is acceptable
     *              through this filter
     */
    public function accept()
    {
        $current = $this->current();
        /** @var $current \SplFileInfo */
        if (! $current->isFile()
            || $current->getSize() === 0
        ) {
            return false;
        }
        return true;
    }
}
