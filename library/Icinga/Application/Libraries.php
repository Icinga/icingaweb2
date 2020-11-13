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
        $libVersion = null;
        foreach ($this->libraries as $library) {
            if ($library->getName() === $name) {
                $libVersion = $library->getVersion();
                break;
            }
        }

        if ($libVersion === null) {
            return false;
        } elseif ($version === null) {
            return true;
        }

        $operator = '=';
        if (preg_match('/^([<>=]{1,2})\s*v?((?:[\d.]+)(?:\D+)?)$/', $version, $match)) {
            $operator = $match[1];
            $version = $match[2];
        }

        return version_compare($libVersion, $version, $operator);
    }
}
