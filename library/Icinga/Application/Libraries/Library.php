<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Libraries;

class Library
{
    protected $path;

    /**
     * Create a new Library
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Register this library's autoloader
     *
     * @return void
     */
    public function registerAutoloader()
    {
        $autoloaderPath = join(DIRECTORY_SEPARATOR, [$this->path, 'vendor', 'autoload.php']);
        if (file_exists($autoloaderPath)) {
            require_once $autoloaderPath;
        }
    }
}
