<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Doc;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Icinga\File\NonEmptyFileIterator;
use Icinga\File\FileExtensionFilterIterator;

/**
 * Iterator over non-empty Markdown files ordered by the case insensitive "natural order" of file names
 */
class DocIterator implements Countable, IteratorAggregate
{
    /**
     * Ordered files
     *
     * @var array
     */
    protected $fileInfo;

    /**
     * Create a new DocIterator
     *
     * @param string $path Path to the documentation
     */
    public function __construct($path)
    {
        $it = new FileExtensionFilterIterator(
            new NonEmptyFileIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path),
                    RecursiveIteratorIterator::SELF_FIRST
                )
            ),
            'md'
        );
        // Unfortunately we have no chance to sort the iterator
        $fileInfo = iterator_to_array($it);
        natcasesort($fileInfo);
        $this->fileInfo = $fileInfo;
    }

    /**
     * (non-PHPDoc)
     * @see Countable::count()
     */
    public function count()
    {
        return count($this->fileInfo);
    }

    /**
     * (non-PHPDoc)
     * @see IteratorAggregate::getIterator()
     */
    public function getIterator()
    {
        return new ArrayIterator($this->fileInfo);
    }
}
