<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Application;

use ArrayIterator;
use IteratorAggregate;
use Icinga\Application\Libraries\Library;

class Libraries implements IteratorAggregate
{
    /** @var Library[] */
    protected $libraries = [];

    /**
     * Iterate over registered libraries
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->libraries);
    }

    /**
     * Register a library from the given path
     *
     * @param string $path
     *
     * @return Library The registered library
     */
    public function registerPath($path)
    {
        $library = new Library($path);
        $this->libraries[] = $library;

        return $library;
    }
}
