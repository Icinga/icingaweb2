<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Object;

use ArrayIterator;
use Countable;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filterable;
use IteratorAggregate;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;

abstract class ObjectList implements Countable, IteratorAggregate, Filterable
{
    /**
     * @var string
     */
    protected $dataViewName;

    /**
     * @var MonitoringBackend
     */
    protected $backend;

    /**
     * @var array
     */
    protected $columns;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var array
     */
    protected $objects;

    /**
     * @var int
     */
    protected $count;

    public function __construct(MonitoringBackend $backend)
    {
        $this->backend = $backend;
    }

    /**
     * @param array $columns
     *
     * @return $this
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param Filter $filter
     *
     * @return $this
     */
    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * @return Filter
     */
    public function getFilter()
    {
        if ($this->filter === null) {
            $this->filter = Filter::matchAll();
        }

        return $this->filter;
    }

    public function applyFilter(Filter $filter)
    {
        $this->getFilter()->addFilter($filter);
        return $this;
    }

    public function addFilter(Filter $filter)
    {
        $this->getFilter()->addFilter($filter);
    }

    public function where($condition, $value = null)
    {
        $this->getFilter()->addFilter(Filter::where($condition, $value));
    }

    abstract protected function fetchObjects();

    /**
     * @return array
     */
    public function fetch()
    {
        if ($this->objects === null) {
            $this->objects = $this->fetchObjects();
        }
        return $this->objects;
    }

    /**
     * @return int
     */
    public function count()
    {
        if ($this->count === null) {
            $this->count = (int) $this->backend
                ->select()
                ->from($this->dataViewName, $this->columns)
                ->applyFilter($this->filter)
                ->getQuery()
                ->count();
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

    /**
     * Get the scheduled downtimes
     *
     * @return type
     */
    public function getScheduledDowntimes()
    {
        return $this->backend->select()->from('downtime')->applyFilter($this->filter);
    }

    /**
     * @return ObjectList
     */
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

    /**
     * @return ObjectList
     */
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

    /**
     * @return ObjectList
     */
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
     * @return ObjectList
     */
    public abstract function getUnacknowledgedObjects();

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
        $list->filter = $list->objectsFilter();
        return $list;
    }

    /**
     * Create a filter that matches exactly the elements of this object list
     *
     * @param   array   $columns    Override default column names.
     *
     * @return  Filter
     */
    abstract function objectsFilter($columns = array());

    /**
     * Get the feature status
     *
     * @return array
     */
    public function getFeatureStatus()
    {
        // null - init
        // 0 - disabled
        // 1 - enabled
        // 2 - enabled & disabled
        $featureStatus = array(
            'active_checks_enabled'     => null,
            'passive_checks_enabled'    => null,
            'obsessing'                 => null,
            'notifications_enabled'     => null,
            'event_handler_enabled'     => null,
            'flap_detection_enabled'    => null
        );

        $features = array();

        foreach ($featureStatus as $feature => &$status) {
            $features[$feature] = &$status;
        }

        foreach ($this as $object) {
            foreach ($features as $feature => &$status) {
                $enabled = (int) $object->{$feature};
                if (! isset($status)) {
                    $status = $enabled;
                } elseif ($status !== $enabled) {
                    $status = 2;
                    unset($features[$status]);
                    if (empty($features)) {
                        break 2;
                    }
                    break;
                }
            }
        }

        return $featureStatus;
    }
}
