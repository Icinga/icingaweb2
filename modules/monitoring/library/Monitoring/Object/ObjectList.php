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
}
