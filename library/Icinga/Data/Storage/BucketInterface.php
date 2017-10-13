<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Storage;

use Icinga\Exception\AlreadyExistsException;
use Icinga\Exception\NotFoundError;
use Icinga\Exception\NotImplementedError;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;

interface BucketInterface
{
    /**
     * Get the parent bucket if this is not a root bucket
     *
     * @return  BucketInterface|null
     */
    public function getParent();

    /**
     * Get all child buckets by name
     *
     * @return  BucketInterface[]
     *
     * @throws  NotReadableError
     */
    public function getChildren();

    /**
     * Get all items by name
     *
     * @return  ItemInterface[]
     *
     * @throws  NotReadableError
     */
    public function getItems();

    /**
     * Get a child bucket by name
     *
     * @param   string  $name
     * 
     * @return  BucketInterface
     *
     * @throws  NotReadableError
     * @throws  NotFoundError
     */
    public function getChild($name);

    /**
     * Get an item by name
     *
     * @param   string  $name
     *
     * @return  ItemInterface
     *
     * @throws  NotReadableError
     * @throws  NotFoundError
     */
    public function getItem($name);

    /**
     * Return whether there's a child bucket with the given name
     *
     * @param   string  $name
     *
     * @return  bool
     *
     * @throws  NotReadableError
     */
    public function hasChild($name);

    /**
     * Return whether there's an item with the given name
     *
     * @param   string  $name
     *
     * @return  bool
     *
     * @throws  NotReadableError
     */
    public function hasItem($name);

    /**
     * Create a new child bucket with the given name
     *
     * @param   string          $name
     * @param   BucketInterface $child
     *
     * @return  BucketInterface
     *
     * @throws  NotImplementedError     If this (kind of) bucket doesn't support child buckets
     * @throws  NotWritableError
     * @throws  AlreadyExistsException
     */
    public function createChild($name, $child = null);

    /**
     * Create a new item with the given name
     *
     * @param   string          $name
     * @param   ItemInterface   $item
     *
     * @return  ItemInterface
     *
     * @throws  NotWritableError
     * @throws  AlreadyExistsException
     */
    public function createItem($name, $item = null);

    /**
     * Create a child bucket with the given name (overwrite if exists)
     *
     * @param   string          $name
     * @param   BucketInterface $child
     *
     * @return  BucketInterface
     *
     * @throws  NotImplementedError     If this (kind of) bucket doesn't support child buckets
     * @throws  NotWritableError
     */
    public function overwriteChild($name, $child = null);

    /**
     * Create an item with the given name (overwrite if exists)
     *
     * @param   string          $name
     * @param   ItemInterface   $item
     *
     * @return  ItemInterface
     *
     * @throws  NotWritableError
     */
    public function overwriteItem($name, $item = null);

    /**
     * Delete a child bucket by name
     *
     * @param   string  $name
     *
     * @return  $this
     *
     * @throws  NotImplementedError     If this (kind of) bucket doesn't support child buckets
     * @throws  NotWritableError
     * @throws  NotFoundError
     */
    public function deleteChild($name);

    /**
     * Delete an item by name
     *
     * @param   string  $name
     *
     * @return  $this
     *
     * @throws  NotWritableError
     * @throws  NotFoundError
     */
    public function deleteItem($name);

    /**
     * Delete a child bucket by name if it exists
     *
     * @param   string  $name
     *
     * @return  bool
     *
     * @throws  NotImplementedError     If this (kind of) bucket doesn't support child buckets
     * @throws  NotWritableError
     */
    public function deleteChildIfExists($name);

    /**
     * Delete an item by name if it exists
     *
     * @param   string  $name
     *
     * @return  bool
     *
     * @throws  NotWritableError
     */
    public function deleteItemIfExists($name);
}
