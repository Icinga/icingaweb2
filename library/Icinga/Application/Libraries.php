<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Application;

use ArrayIterator;
use IteratorAggregate;
use Icinga\Application\Libraries\Library;
use Traversable;

class Libraries implements IteratorAggregate
{
    /** @var Library[] */
    protected $libraries = [];

    /**
     * Iterate over registered libraries
     *
     * @return ArrayIterator
     */
    public function getIterator(): Traversable
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

    /**
     * Check if a library with the given name has been registered
     *
     * Passing a version constraint also verifies that the library's version matches.
     *
     * @param string $name
     * @param string $version
     *
     * @return bool
     */
    public function has($name, $version = null)
    {
        $library = $this->get($name);
        if ($library === null) {
            return false;
        } elseif ($version === null || $version === true) {
            return true;
        }

        $operator = '=';
        if (preg_match('/^([<>=]{1,2})\s*v?((?:[\d.]+)(?:\D+)?)$/', $version, $match)) {
            $operator = $match[1];
            $version = $match[2];
        }

        return version_compare($library->getVersion(), $version, $operator);
    }

    /**
     * Get a library by name
     *
     * @param string $name
     *
     * @return Library|null
     */
    public function get($name)
    {
        $candidate = null;
        foreach ($this->libraries as $library) {
            $libraryName = $library->getName();
            if ($libraryName === $name) {
                return $library;
            } elseif (explode('/', $libraryName)[1] === $name) {
                // Also return libs which only partially match
                $candidate = $library;
            }
        }

        return $candidate;
    }
}
