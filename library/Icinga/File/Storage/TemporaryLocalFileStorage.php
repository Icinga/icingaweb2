<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Storage;

use ErrorException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Stores files in a temporary directory
 */
class TemporaryLocalFileStorage extends LocalFileStorage
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid();
        mkdir($path, 0700);

        parent::__construct($path);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $directoryIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->baseDir,
                RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
                    | RecursiveDirectoryIterator::KEY_AS_PATHNAME
                    | RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($directoryIterator as $path => $entry) {
            /** @var \SplFileInfo $entry */

            if ($entry->isDir() && ! $entry->isLink()) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($this->baseDir);
    }
}
