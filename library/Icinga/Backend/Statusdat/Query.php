<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Backend\Statusdat;

use Icinga\Backend\Criteria\Order;
use Icinga\Backend\MonitoringObjectList as MList;
use Icinga\Protocol\Statusdat;
use Icinga\Exception;
use Icinga\Backend\Query as BaseQuery;

/**
 * Class Query
 * @package Icinga\Backend\Statusdat
 */
abstract class Query extends BaseQuery
{
    /**
     * @var null
     */
    protected $cursor = null;

    /**
     * @var string
     */
    protected $view = 'Icinga\Backend\Statusdat\DataView\StatusdatServiceView';

    /**
     * @var array Mapping of order to field names
     * @todo Is not complete right now
     */
    protected $orderColumns = array(
        Order::SERVICE_STATE => "status.current_state",
        Order::STATE_CHANGE => "status.last_state_change",
        Order::HOST_STATE => "status.current_state",
        Order::HOST_NAME => "host_name",
        Order::SERVICE_NAME => "service_description"
    );

    /**
     * Calls the apply%Filtername%Filter() method for the given filter, or simply calls
     * where(), if the method is not available.
     *
     * @see \Icinga\Backend\Query   For the parent definition
     *
     * @param array $filters    An array of "filtername"=>"value" definitions
     *
     * @return Query  Returns the query object to allow fluent calls
     */
    public function applyFilters(array $filters = array())
    {
        foreach ($filters as $filter => $value) {
            $filter[0] = strtoupper($filter[0]);
            $filterMethod = "apply" . $filter . "Filter";
            if (method_exists($this, $filterMethod)) {
                $this->$filterMethod($filter, $value);
            } else {
                $this->where($filter, $value);
            }
        }
        return $this;
    }

    /**
     * Applies a filter to only show open problems, or non problems, depending whether value is true or false
     *
     * @param $type     ignored
     * @param $value    Whether problems should be shown (1) or non problems (0)
     */
    public function applyProblemsFilter($type, $value)
    {
        if ($value) { // Status.dat only contains active downtimes
            $value = array(1, 0);
            $this->where("(status.current_state >= ? and COUNT{status.downtime} = ? )", $value);
        } else {
            $value = array(0, 1);
            $this->where("(status.current_state < 1 or COUNT{status.downtime} > ? )", $value);
        }
    }

    /**
     * Generic object search by host name, service description and plugin output
     *
     * @param $type     ignored
     * @param $value    The string to search for
     */
    public function applySearchFilter($type, $value)
    {
        $text = "%$value%";
        $val = array($text, $text, $text);

        $this->query->where("(host_name LIKE ? OR service_description LIKE ? OR status.plugin_output LIKE ?)", $val);

    }

    /**
     * Applies a hostgroup filter on this object
     *
     * @param $type     ignored
     * @param $value    The hostgroup to filter for
     */
    public function applyHostgroupsFilter($type, $value)
    {
        $filter = array($value);
        $this->query->where("host.group IN ?", $filter);
    }

    /**
     * Applies a servicegroup filter on this object
     *
     * @param $type     ignored
     * @param $value    The servicegroup to filter for
     */
    public function applyServicegroupsFilter($type, $value)
    {
        $filter = array($value);
        $this->query->where("group IN ?", $filter);
    }

    /**
     * Filters by handled problems or unhandled
     *
     * @todo: Add downtime
     * @param $type
     * @param $value Whether to search for unhandled (0) or handled (1)
     */
    public function applyHandledFilter($type, $value)
    {
        $val = array($value, $value);
        $this->query->where("(status.problem_has_been_acknowledged = ? )", $val);
    }

    /**
     * @param $type
     * @param $value
     */
    public function applyHostnameFilter($type, $value)
    {
        if (!is_array($value)) {
            $value = array($value);
        }
        $this->query->where("host_name LIKE ?", $value);
    }

    /**
     * @param $type
     * @param $value
     */
    public function applyStateFilter($type, $value)
    {
        $this->query->where("status.current_state = $value");
    }

    /**
     * @param $type
     * @param $value
     */
    public function applyHoststateFilter($type, $value)
    {
        $this->query->where("host.status.current_state = $value");
    }

    /**
     * @param $type
     * @param $value
     */
    public function applyServiceDescriptionFilter($type, $value)
    {
        if (!is_array($value)) {
            $value = array($value);
        }
        $this->query->where("service_description LIKE ?", $value);
    }

    /**
     * Limits this query and offsets it
     * @param null|integer $count   The maximum element count to display
     * @param null|integer $offset  The offset to start counting
     * @return Query                This object, for fluent interface
     */
    public function limit($count = null, $offset = null)
    {
        $this->query->limit($count, $offset);
        return $this;
    }

    /**
     * Orders the resultset
     *
     * @param string $column    Either a string in the 'FIELD ASC/DESC format or only the field
     * @param null $dir 'asc' or 'desc'
     * @return Query            Returns this query,for fluent interface
     */
    public function order($column = '', $dir = null)
    {

        if ($column) {
            $this->query->order($this->orderColumns[$column], strtolower($dir));
        }
        return $this;
    }

    /**
     * Applies a filter on this query by calling the statusdat where() function
     *
     * @param $column       The (statusdat!) column to filter in "field operator ?"
     *                      format. (@example status.current_state > ?)
     * @param mixed $value  The value to filter for
     * @return Query        Returns this query,for fluent interface
     */
    public function where($column, $value = null)
    {
        if (!is_array($value)) {
            $value = array($value);
        }
        $this->query->where($column, $value);
        return $this;
    }

    /**
     * @return MList|mixed|null
     */
    public function fetchAll()
    {
        $view = $this->view;
        if (!$this->cursor) {
            $this->cursor = new MList($this->query->getResult(), new $view($this->reader));
        }
        return $this->cursor;
    }

    /**
     * @return mixed
     */
    public function fetchRow()
    {
        return next($this->fetchAll());
    }

    /**
     * @return mixed|void
     */
    public function fetchPairs()
    {

    }

    /**
     * @return mixed
     */
    public function fetchOne()
    {
        return next($this->fetchAll());
    }

    /**
     * @return int|mixed
     */
    public function count()
    {
        return count($this->query->getResult());
    }
}
