<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 * 
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

use \Icinga\Module\Monitoring\Backend\Statusdat\Criteria\Order;
use Icinga\Protocol\Statusdat;
use Icinga\Exception;
use Icinga\Data\AbstractQuery;
use Icinga\Protocol\Statusdat\View\MonitoringObjectList as MList;
use Icinga\Protocol\Statusdat\Query as StatusdatQuery;
/**
 * Class Query
 * @package Icinga\Backend\Statusdat
 */
abstract class Query extends AbstractQuery
{
    /**
     * @var null
     */
    private $cursor = null;

    /**
     * @var string
     */
    private $viewClass = '\Monitoring\Backend\Statusdat\DataView\StatusdatServiceView';
    private $baseQuery = null;

    public function setBaseQuery(StatusdatQuery $query)
    {
        $this->baseQuery = $query;
    }

    public function setResultViewClass($viewClass)
    {
        $this->viewClass = '\Monitoring\Backend\Statusdat\DataView\\'.$viewClass;
    }


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

        $this->baseQuery->where("(host_name LIKE ? OR service_description LIKE ? OR status.plugin_output LIKE ?)", $val);

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
        $this->baseQuery->where("host.group IN ?", $filter);
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
        $this->baseQuery->where("group IN ?", $filter);
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
        $this->baseQuery->where("(status.problem_has_been_acknowledged = ? )", $val);
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
        $this->baseQuery->where("host_name LIKE ?", $value);
    }

    /**
     * @param $type
     * @param $value
     */
    public function applyStateFilter($type, $value)
    {
        $this->baseQuery->where("status.current_state = $value");
    }

    /**
     * @param $type
     * @param $value
     */
    public function applyHoststateFilter($type, $value)
    {
        $this->baseQuery->where("host.status.current_state = $value");
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
        $this->baseQuery->where("service_description LIKE ?", $value);
    }

    /**
     * Limits this query and offsets it
     * @param null|integer $count   The maximum element count to display
     * @param null|integer $offset  The offset to start counting
     * @return Query                This object, for fluent interface
     */
    public function limit($count = null, $offset = null)
    {
        $this->baseQuery->limit($count, $offset);
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
            $class = $this->viewClass;
            $this->baseQuery->order($class::$mappedParameters[$column], strtolower($dir));
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
        $this->baseQuery->where($column, $value);
        return $this;
    }

    /**
     * @return MList|mixed|null
     */
    public function fetchAll()
    {
        $view = $this->viewClass;
        if (!$this->cursor) {
            $result = $this->baseQuery->getResult();
            $this->cursor = new MList($result, new $view($this->reader));
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

        return count($this->baseQuery->getResult());
    }
}
