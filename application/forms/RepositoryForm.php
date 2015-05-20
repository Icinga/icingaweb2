<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms;

use Exception;
use Icinga\Data\Filter\Filter;
use Icinga\Repository\Repository;
use Icinga\Web\Form;
use Icinga\Web\Notification;

/**
 * Form base-class providing standard functionality for extensible, updatable and reducible repositories
 */
abstract class RepositoryForm extends Form
{
    /**
     * Insert mode
     */
    const MODE_INSERT = 0;

    /**
     * Update mode
     */
    const MODE_UPDATE = 1;

    /**
     * Delete mode
     */
    const MODE_DELETE = 2;

    /**
     * The repository being worked with
     *
     * @var Repository 
     */
    protected $repository;

    /**
     * How to interact with the repository
     *
     * @var int
     */
    protected $mode;

    /**
     * The name of the entry being handled when in mode update or delete
     *
     * @var string
     */
    protected $identifier;

    /**
     * The data of the entry to pre-populate the form with when in mode insert or update
     *
     * @var type 
     */
    protected $data;

    /**
     * Set the repository to work with
     *
     * @param   Repository  $repository
     *
     * @return  $this
     */
    public function setRepository(Repository $repository)
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * Return the name of the entry to handle
     *
     * @return  string
     */
    protected function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Return the current data of the entry being handled
     *
     * @return  array
     */
    protected function getData()
    {
        return $this->data;
    }

    /**
     * Return whether an entry should be inserted
     *
     * @return  bool
     */
    public function shouldInsert()
    {
        return $this->mode === self::MODE_INSERT;
    }

    /**
     * Return whether an entry should be udpated
     *
     * @return  bool
     */
    public function shouldUpdate()
    {
        return $this->mode === self::MODE_UPDATE;
    }

    /**
     * Return whether an entry should be deleted
     *
     * @return  bool
     */
    public function shouldDelete()
    {
        return $this->mode === self::MODE_DELETE;
    }

    /**
     * Add a new entry
     *
     * @param   array   $data   The defaults to use, if any
     *
     * @return  $this
     */
    public function add(array $data = array())
    {
        $this->mode = static::MODE_INSERT;
        $this->data = $data;
        return $this;
    }

    /**
     * Edit an entry
     *
     * @param   string  $name   The entry's name
     * @param   array   $data   The entry's current data
     *
     * @return  $this
     */
    public function edit($name, array $data = array())
    {
        $this->mode = static::MODE_UPDATE;
        $this->identifier = $name;
        $this->data = $data;
        return $this;
    }

    /**
     * Remove an entry
     *
     * @param   string  $name   The entry's name
     *
     * @return  $this
     */
    public function remove($name)
    {
        $this->mode = static::MODE_DELETE;
        $this->identifier = $name;
        return $this;
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData   The data sent by the user
     */
    public function createElements(array $formData)
    {
        if ($this->shouldInsert()) {
            $this->createInsertElements($formData);
        } elseif ($this->shouldUpdate()) {
            $this->createUpdateElements($formData);
        } elseif ($this->shouldDelete()) {
            $this->createDeleteElements($formData);
        }
    }

    /**
     * Populate the data of the entry being handled
     */
    public function onRequest()
    {
        $data = $this->getData();
        if (! empty($data)) {
            $this->populate($data);
        }
    }

    /**
     * Apply the requested mode on the repository
     *
     * @return  bool
     */
    public function onSuccess()
    {
        if ($this->shouldInsert()) {
            return $this->onInsertSuccess();
        } elseif ($this->shouldUpdate()) {
            return $this->onUpdateSuccess();
        } elseif ($this->shouldDelete()) {
            return $this->onDeleteSuccess();
        }
    }

    /**
     * Apply mode insert on the repository
     *
     * @return  bool
     */
    protected function onInsertSuccess()
    {
        try {
            $this->repository->insert(
                $this->repository->getBaseTable(),
                $this->getValues()
            );
        } catch (Exception $e) {
            Notification::error($this->getInsertMessage(false));
            $this->error($e->getMessage());
            return false;
        }

        Notification::success($this->getInsertMessage(true));
        return true;
    }

    /**
     * Apply mode update on the repository
     *
     * @return  bool
     */
    protected function onUpdateSuccess()
    {
        try {
            $this->repository->update(
                $this->repository->getBaseTable(),
                $this->getValues(),
                $this->createFilter()
            );
        } catch (Exception $e) {
            Notification::error($this->getUpdateMessage(false));
            $this->error($e->getMessage());
            return false;
        }

        Notification::success($this->getUpdateMessage(true));
        return true;
    }

    /**
     * Apply mode delete on the repository
     *
     * @return  bool
     */
    protected function onDeleteSuccess()
    {
        try {
            $this->repository->delete(
                $this->repository->getBaseTable(),
                $this->createFilter()
            );
        } catch (Exception $e) {
            Notification::error($this->getDeleteMessage(false));
            $this->error($e->getMessage());
            return false;
        }

        Notification::success($this->getDeleteMessage(true));
        return true;
    }

    /**
     * Create and add elements to this form to insert an entry
     *
     * @param   array   $formData   The data sent by the user
     */
    abstract protected function createInsertElements(array $formData);

    /**
     * Create and add elements to this form to update an entry
     *
     * Calls createInsertElements() by default. Overwrite this to add different elements when in mode update.
     *
     * @param   array   $formData   The data sent by the user
     */
    protected function createUpdateElements(array $formData)
    {
        $this->createInsertElements($formData);
    }

    /**
     * Create and add elements to this form to delete an entry
     *
     * @param   array   $formData   The data sent by the user
     */
    abstract protected function createDeleteElements(array $formData);

    /**
     * Create and return a filter to use when selecting, updating or deleting an entry
     *
     * @return  Filter
     */
    abstract protected function createFilter();

    /**
     * Return a notification message to use when inserting an entry
     *
     * @param   bool    $success    true or false, whether the operation was successful
     *
     * @return  string
     */
    abstract protected function getInsertMessage($success);

    /**
     * Return a notification message to use when updating an entry
     *
     * @param   bool    $success    true or false, whether the operation was successful
     *
     * @return  string
     */
    abstract protected function getUpdateMessage($success);

    /**
     * Return a notification message to use when deleting an entry
     *
     * @param   bool    $success    true or false, whether the operation was successful
     *
     * @return  string
     */
    abstract protected function getDeleteMessage($success);
}
