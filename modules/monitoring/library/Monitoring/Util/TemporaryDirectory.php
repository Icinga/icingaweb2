<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Util;

use ErrorException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class TemporaryDirectory
{
    /**
     * The directory's absolute path
     *
     * @var string
     */
    protected $path;

    /**
     * Constructor
     */
    public function __construct()
    {
        $tempRootDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR;

        for (;;) {
            $this->path = $tempRootDir . uniqid();
            try {
                mkdir($this->path, 0700);
            } catch (ErrorException $e) {
                continue;
            }

            break;
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->path,
                RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
                | RecursiveDirectoryIterator::KEY_AS_PATHNAME
                | RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        ) as $path => $entry) {
            /** @var SplFileInfo $entry */

            if ($entry->isDir()) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($this->path);
    }

    /**
     * Return the directory's absolute path
     *
     * @return string
     */
    public function __toString()
    {
        return $this->path;
    }
}
