<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Storage;

use Icinga\Exception\AlreadyExistsException;
use Icinga\Exception\NotFoundError;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;
use IteratorAggregate;
use Traversable;

interface StorageInterface extends IteratorAggregate
{
    /**
     * Iterate over all existing files' paths
     *
     * @return  Traversable
     *
     * @throws  NotReadableError    If the file list can't be read
     */
    public function getIterator();

    /**
     * Return whether the given file exists
     *
     * @param   string  $path
     *
     * @return  bool
     */
    public function has($path);

    /**
     * Create the given file with the given content
     *
     * @param   string  $path
     * @param   mixed   $content
     *
     * @return  $this
     *
     * @throws  AlreadyExistsException  If the file already exists
     * @throws  NotWritableError        If the file can't be written to
     */
    public function create($path, $content);

    /**
     * Load the content of the given file
     *
     * @param   string  $path
     *
     * @return  mixed
     *
     * @throws  NotFoundError       If the file can't be found
     * @throws  NotReadableError    If the file can't be read
     */
    public function read($path);

    /**
     * Overwrite the given file with the given content
     *
     * @param   string  $path
     * @param   mixed   $content
     *
     * @return  $this
     *
     * @throws  NotFoundError       If the file can't be found
     * @throws  NotWritableError    If the file can't be written to
     */
    public function update($path, $content);

    /**
     * Delete the given file
     *
     * @param   string  $path
     *
     * @return  $this
     *
     * @throws  NotFoundError       If the file can't be found
     * @throws  NotWritableError    If the file can't be deleted
     */
    public function delete($path);

    /**
     * Get the absolute path to the given file
     *
     * @param   string  $path
     * @param   bool    $assertExistence    Whether to require that the given file exists
     *
     * @return  string
     *
     * @throws  NotFoundError       If the file has to exist, but can't be found
     */
    public function resolvePath($path, $assertExistence = false);
}
