<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Doc;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

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
        $it = new RecursiveIteratorIterator(
            new NonEmptyFileIterator(
                new MarkdownFileIterator(
                    new RecursiveDirectoryIterator($path)
                )
            )
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
