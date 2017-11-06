<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Storage;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @deprecated This class will be removed once we require PHP 5.6
 */
class LocalFileStorageIterator extends RecursiveIteratorIterator
{
    /**
     * Constructor
     *
     * @param   string  $baseDir
     */
    public function __construct($baseDir)
    {
        parent::__construct(new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS));
    }

    public function key()
    {
        parent::key();
        return $this->current();
    }

    public function current()
    {
        /** @var RecursiveDirectoryIterator $innerIterator */
        $innerIterator = $this->getInnerIterator();

        /** @var \SplFileInfo $current */
        $current = parent::current();

        $subPath = $innerIterator->getSubPath();

        return $subPath === ''
            ? $current->getFilename()
            : str_replace(DIRECTORY_SEPARATOR, '/', $subPath) . '/' . $current->getFilename();
    }
}
