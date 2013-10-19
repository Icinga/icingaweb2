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

namespace Icinga\Protocol\Statusdat;

use Exception;
use Icinga\Filter\Query\Node;
use Icinga\Protocol;
use Icinga\Data\BaseQuery;
use Icinga\Protocol\Statusdat\View\MonitoringObjectList;
use Icinga\Protocol\Statusdat\Query\IQueryPart;

/**
 * Class Query
 * @package Icinga\Protocol\Statusdat
 */
class Query extends BaseQuery
{
    /**
     * @var array
     */
    public static $VALID_TARGETS = array(
        "hosts"         => array("host"),
        "services"      => array("service"),
        "downtimes"     => array("downtime"),
        "groups"        => array("hostgroup", "servicegroup"),
        "hostgroups"    => array("hostgroup"),
        "servicegroups" => array("servicegroup"),
        "comments"      => array("comment"),
        "contacts"      => array("contact"),
        "contactgroups" => array("contactgroup")
    );

    private $queryFilter = null;

    /**
     * @var string
     */
    private $source = "";

    /**
     * @var null
     */
    private $limit = null;

    /**
     * @var int
     */
    private $offset = 0;

    /**
     * @var array
     */
    protected $orderColumns = array();

    /**
     * @var array
     */
    private $groupColumns = array();

    /**
     * @var null
     */
    private $groupByFn = null;

    /**
     * @var array
     */
    private $filter = null;

    /**
     * @var array
     */
    private $attributes = array();

    /**
     *
     */
    const FN_SCOPE = 0;

    /**
     *
     */
    const FN_NAME = 1;

    /**
     * @return bool
     */
    public function hasOrder()
    {
        return !empty($this->orderColumns);
    }

    /**
     * @return bool
     */
    public function hasColumns()
    {
        $columns = $this->getColumns();
        return !empty($columns);
    }

    public function setQueryFilter($filter)
    {
        $this->queryFilter = $filter;
    }

    /**
     * @return bool
     */
    public function hasLimit()
    {
        return $this->limit !== false;
    }

    /**
     * @return bool
     */
    public function hasOffset()
    {
        return $this->offset !== false;
    }

    /**
     * @return null
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int|null
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param $columns
     * @param null $dir
     * @return $this
     */
    public function order($columns, $dir = null, $isFunction = false)
    {
        if ($dir && strtolower($dir) == "desc") {
            $dir = self::SORT_DESC;
        } else {
            $dir = self::SORT_ASC;
        }
        if ($isFunction) {
            $this->orderColumns[] = array($columns, $dir);
            return $this;
        }
        if (!is_array($columns)) {
            $columns = array($columns);
        }

        foreach ($columns as $col) {
            if (($pos = strpos($col, ' ')) !== false) {
                $dir = strtoupper(substr($col, $pos + 1));
                if ($dir === 'DESC') {
                    $dir = self::SORT_DESC;
                } else {
                    $dir = self::SORT_ASC;
                }
                $col = substr($col, 0, $pos);
            } else {
                $col = $col;
            }

            $this->orderColumns[] = array($col, $dir);
        }
        return $this;
    }

    public function orderByFn(array $callBack, $dir = null)
    {
        $this->order($callBack, $dir, true);
    }

    /**
     * @param null $count
     * @param int $offset
     * @return $this
     * @throws Exception
     */
    public function limit($count = null, $offset = 0)
    {
        if ((is_null($count) || is_integer($count)) && (is_null($offset) || is_integer($offset))) {
            $this->offset = $offset;
            $this->limit = $count;
        } else {
            throw new Exception("Got invalid limit $count, $offset");
        }
        return $this;
    }


    /**
     * @param $table
     * @param null $columns
     * @return $this
     * @throws \Exception
     */
    public function from($table, array $attributes = null)
    {
        if (!$this->getColumns() && $attributes) {
            $this->setColumns($attributes);
        }
        if (isset(self::$VALID_TARGETS[$table])) {
            $this->source = $table;
        } else {
            throw new \Exception("Unknown from target for status.dat :" . $table);
        }
        return $this;
    }

    /**
     *
     * @throws Exception
     */
    private function getFilteredIndices($classType = "\Icinga\Protocol\Statusdat\Query\Group")
    {
        $baseGroup = $this->queryFilter;


        $state = $this->ds->getObjects();
        $result = array();
        $source = self::$VALID_TARGETS[$this->source];

        foreach ($source as $target) {
            if (! isset($state[$target])) {
                continue;
            }
            $indexes = array_keys($state[$target]);
            if ($baseGroup) {
                $baseGroup->setQuery($this);
                $indexes = $baseGroup->filter($state[$target]);
            }
            if (!isset($result[$target])) {
                $result[$target] = $indexes;
            } else {
                array_merge($result[$target], $indexes);
            }
        }
        return $result;
    }

    /**
     * @param array $indices
     */
    private function orderIndices(array &$indices)
    {
        if (!empty($this->orderColumns)) {
            foreach ($indices as $type => &$subindices) {
                $this->currentType = $type;
                usort($subindices, array($this, "orderResult"));
            }
        }
    }

    public function select()
    {
        return $this;
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    private function orderResult($a, $b)
    {
        $o1 = $this->ds->getObjectByName($this->currentType, $a);
        $o2 = $this->ds->getObjectByName($this->currentType, $b);
        $result = 0;

        foreach ($this->orderColumns as &$col) {
            if (is_array($col[0])) {
                $result += $col[1] * strnatcasecmp($col[0][0]->$col[0][1]($o1), $col[0][0]->$col[0][1]($o2));
            } else {
                if (is_string($o1->{$col[0]}) && is_string($o2->{$col[0]}) ) {
                    $result += $col[1] * strnatcasecmp($o1->{$col[0]}, $o2->{$col[0]});
                }
            }
        }
        return $result;
    }

    /**
     * @param array $indices
     */
    private function limitIndices(array &$indices)
    {
        foreach ($indices as $type => $subindices) {
            $indices[$type] = array_slice($subindices, $this->offset, $this->limit);
        }
    }

    /**
     * @param $fn
     * @param null $scope
     * @return $this
     */
    public function groupByFunction($fn, $scope = null)
    {
        $this->groupByFn = array($scope ? $scope : $this, $fn);
        return $this;
    }

    /**
     * @param $columns
     * @return $this
     */
    public function groupByColumns($columns)
    {
        if (!is_array($columns)) {
            $columns = array($columns);
        }
        $this->groupColumns = $columns;
        $this->groupByFn = array($this, "columnGroupFn");
        return $this;
    }

    /**
     * @param array $indices
     * @return array
     */
    private function columnGroupFn(array &$indices)
    {
        $cols = $this->groupColumns;
        $result = array();
        foreach ($indices as $type => $subindices) {
            foreach ($subindices as $objectIndex) {
                $r = $this->ds->getObjectByName($type, $objectIndex);
                $hash = "";
                $cols = array();
                foreach ($this->groupColumns as $col) {
                    $hash = md5($hash . $r->$col);
                    $cols[$col] = $r->$col;
                }
                if (!isset($result[$hash])) {
                    $result[$hash] = (object)array(
                        "columns" => (object)$cols,
                        "count" => 0
                    );
                }
                $result[$hash]->count++;
            }
        }
        return array_values($result);
    }

    /**
     * @return array
     */
    public function getResult()
    {

        $indices = $this->getFilteredIndices();
        $this->orderIndices($indices);
        if ($this->groupByFn) {
            $scope = $this->groupByFn[self::FN_SCOPE];
            $fn = $this->groupByFn[self::FN_NAME];

            return $scope->$fn($indices);
        }

        $this->limitIndices($indices);

        $result = array();
        $state = $this->ds->getObjects();
        foreach ($indices as $type => $subindices) {
            foreach ($subindices as $index) {
                $result[] = & $state[$type][$index];
            }
        }
        return $result;
    }

 
    /**
     * Apply all filters of this filterable on the datasource
     */
    public function applyFilter()
    {
        $parser = new TreeToStatusdatQueryParser();
        if ($this->getFilter()) {
            $query = $parser->treeToQuery($this->getFilter());
            $this->setQueryFilter($query);
        }

    }

    /**
     * @return mixed
     */
    public function fetchRow()
    {
        $result =  $this->fetchAll();
        return $result;
    }

    /**
     * @return mixed|void
     */
    public function fetchPairs()
    {
        $result = array();
        if (count($this->getColumns()) < 2) {
            throw new Exception(
                'Status.dat "fetchPairs()" query expects at least' .
                ' columns to be set in the query expression'
            );
        }
        $attributes = $this->getColumns();

        $param1 = $attributes[0];
        $param2 = $attributes[1];
        foreach ($this->fetchAll() as $resultList) {
            $result[$resultList->$param1] = $resultList->$param2;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function fetchOne()
    {
        return next($this->fetchAll());
    }

    /**
     * @return MList|mixed|null
     */
    public function fetchAll()
    {
        $this->applyFilter();
        if (!isset($this->cursor)) {
            $result = $this->getResult();
            $this->cursor = new MonitoringObjectList($result, $this);
        }
        return $this->cursor;
    }

    /**
     * @return int|mixed
     */
    public function count()
    {
        $q = clone $this;
        $q->limit(null, null);
        return count($q->fetchAll());
    }


}
