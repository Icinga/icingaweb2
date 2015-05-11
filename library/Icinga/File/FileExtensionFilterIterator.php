<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\File;

use FilterIterator;
use Iterator;

/**
 * Iterator over files having a specific file extension
 *
 * Usage example:
 * <code>
 * <?php
 *
 * namespace Icinga\Example;
 *
 * use RecursiveDirectoryIterator;
 * use RecursiveIteratorIterator;
 * use Icinga\File\FileExtensionFilterIterator;
 *
 * $markdownFiles = new FileExtensionFilterIterator(
 *     new RecursiveIteratorIterator(
 *         new RecursiveDirectoryIterator(__DIR__),
 *         RecursiveIteratorIterator::SELF_FIRST
 *     ),
 *     'md'
 * );
 * </code>
 */
class FileExtensionFilterIterator extends FilterIterator
{
    /**
     * The extension to filter for
     *
     * @var string
     */
    protected $extension;

    /**
     * Create a new FileExtensionFilterIterator
     *
     * @param Iterator  $iterator   Apply filter to this iterator
     * @param string    $extension  The file extension to filter for. The file extension may not contain the leading dot
     */
    public function __construct(Iterator $iterator, $extension)
    {
        $this->extension = '.' . ltrim(strtolower((string) $extension), '.');
        parent::__construct($iterator);
    }

    /**
     * Accept files which match the file extension to filter for
     *
     * @return bool Whether the current element of the iterator is acceptable
     *              through this filter
     */
    public function accept()
    {
        $current = $this->current();
        /** @var $current \SplFileInfo */
        if (! $current->isFile()) {
            return false;
        }
        // SplFileInfo::getExtension() is only available since PHP 5 >= 5.3.6
        $filename = $current->getFilename();
        $sfx = substr($filename, -strlen($this->extension));
        return $sfx === false ? false : strtolower($sfx) === $this->extension;
    }
}
