<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Object;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;

abstract class ObjectList implements Countable, IteratorAggregate
{
    protected $dataViewName;

    protected $backend;

    protected $columns;

    protected $filter;

    protected $objects;

    protected $count;

    public function __construct(MonitoringBackend $backend)
    {
        $this->backend = $backend;
    }

    public function setColumns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    public function getFilter()
    {
        return $this->filter;
    }

    abstract protected function fetchObjects();

    public function fetch()
    {
        if ($this->objects === null) {
            $this->objects = $this->fetchObjects();
        }
        return $this->objects;
    }

    public function count()
    {
        if ($this->count === null) {
            $this->count = (int) $this->backend->select()->from($this->dataViewName)->applyFilter($this->filter)
                ->getQuery()->count();
        }
        return $this->count;
    }

    public function getIterator()
    {
        if ($this->objects === null) {
            $this->fetch();
        }
        return new ArrayIterator($this->objects);
    }

    /**
     * Get the comments
     *
     * @return \Icinga\Module\Monitoring\DataView\Comment
     */
    public function getComments()
    {
        return $this->backend->select()->from('comment')->applyFilter($this->filter);
    }

    public function getAcknowledgedObjects()
    {
        $acknowledgedObjects = array();
        foreach ($this as $object) {
            if ((bool) $object->acknowledged === true) {
                $acknowledgedObjects[] = $object;
            }
        }
        return $this->newFromArray($acknowledgedObjects);
    }

    public function getObjectsInDowntime()
    {
        $objectsInDowntime = array();
        foreach ($this as $object) {
            if ((bool) $object->in_downtime === true) {
                $objectsInDowntime[] = $object;
            }
        }
        return $this->newFromArray($objectsInDowntime);
    }

    public function getUnhandledObjects()
    {
        $unhandledObjects = array();
        foreach ($this as $object) {
            if ((bool) $object->problem === true && (bool) $object->handled === false) {
                $unhandledObjects[] = $object;
            }
        }
        return $this->newFromArray($unhandledObjects);
    }

    /**
     * @return ObjectList
     */
    public function getProblemObjects()
    {
        $handledObjects = array();
        foreach ($this as $object) {
            if ((bool) $object->problem === true) {
                $handledObjects[] = $object;
            }
        }
        return $this->newFromArray($handledObjects);
    }

    /**
     * Create a ObjectList from an array of hosts without querying a backend
     *
     * @return ObjectList
     */
    protected function newFromArray(array $objects)
    {
        $class = get_called_class();
        $list = new $class($this->backend);
        $list->objects = $objects;
        $list->count = count($objects);
        $list->filter = $list->filterFromResult();
        return $list;
    }

    abstract function filterFromResult();
}
