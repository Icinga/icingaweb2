<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Statusdat;

use Exception;
use Icinga\Exception\ProgrammingError;
use Icinga\Filter\Query\Node;
use Icinga\Protocol;
use Icinga\Data\BaseQuery;
use Icinga\Protocol\Statusdat\View\MonitoringObjectList;
use Icinga\Protocol\Statusdat\Query\IQueryPart;

/**
 * Base implementation for Statusdat queries.
 */
class Query extends BaseQuery
{
    /**
     * An array denoting valid targets by mapping the query target to
     * the 'define' directives found in the status.dat/objects.cache files
     *
     * @var array
     */
    public static $VALID_TARGETS = array(
        'hosts'         => array('host'),
        'services'      => array('service'),
        'downtimes'     => array('downtime'),
        'groups'        => array('hostgroup', 'servicegroup'),
        'hostgroups'    => array('hostgroup'),
        'servicegroups' => array('servicegroup'),
        'comments'      => array('comment'),
        'contacts'      => array('contact'),
        'contactgroups' => array('contactgroup')
    );

    /**
     * The current StatusDat query that will be applied upon calling fetchAll
     *
     * @var IQueryPart
     */
    private $queryFilter = null;

    /**
     * The current query source being used
     *
     * @var string
     */
    private $source = '';

    /**
     * An array containing all columns used for sorting
     *
     * @var array
     */
    protected $orderColumns = array();

    /**
     * An array containig all columns used for (simple) grouping
     *
     * @var array
     */
    private $groupColumns = array();

    /**
     * An optional function callback to use for more specific grouping
     *
     * @var array
     */
    private $groupByFn = null;

    /**
     * The scope index for the callback function
     */
    const FN_SCOPE = 0;

    /**
     * The name index for the callback function
     */
    const FN_NAME = 1;

    /**
     * Return true if columns are set for this query
     *
     * @return bool
     */
    public function hasColumns()
    {
        $columns = $this->getColumns();
        return !empty($columns);
    }

    /**
     * Set the status.dat specific IQueryPart filter to use
     *
     * @param IQueryPart $filter
     */
    public function setQueryFilter($filter)
    {
        $this->queryFilter = $filter;
    }

    /**
     * Order the query result by the given columns
     *
     * @param String|array  $columns    An array of columns to order by
     * @param String        $dir        The direction (asc or desc) in string form
     *
     * @return $this                    Fluent interface
     */
    public function order($columns, $dir = null, $isFunction = false)
    {
        if ($dir && strtolower($dir) == 'desc') {
            $dir = self::SORT_DESC;
        } else {
            $dir = self::SORT_ASC;
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

    /**
     * Order the query result using the callback to retrieve values for items
     *
     * @param array  $columns    A scope, function array to use for retrieving the values when ordering
     * @param String $dir        The direction (asc or desc) in string form
     *
     * @return $this             Fluent interface
     */
    public function orderByFn(array $callBack, $dir = null)
    {
        if ($dir && strtolower($dir) == 'desc') {
            $dir = self::SORT_DESC;
        } else {
            $dir = self::SORT_ASC;
        }
        $this->orderColumns[] = array($callBack, $dir);
    }



    /**
     * Set the query target
     *
     * @param String $table     The table/target to select the query from
     * @param array  $columns   An array of attributes to use (required for fetchPairs())
     *
     * @return $this            Fluent interface
     * @throws \Exception       If the target is unknonw
     */
    public function from($table, array $attributes = null)
    {
        if (!$this->getColumns() && $attributes) {
            $this->setColumns($attributes);
        }
        if (isset(self::$VALID_TARGETS[$table])) {
            $this->source = $table;
        } else {
            throw new \Exception('Unknown from target for status.dat :' . $table);
        }
        return $this;
    }

    /**
     * Return an index of all objects matching the filter of this query
     *
     * This index will be used for ordering, grouping and limiting
     */
    private function getFilteredIndices($classType = '\Icinga\Protocol\Statusdat\Query\Group')
    {
        $baseGroup = $this->queryFilter;
        $state = $this->ds->getState();
        $result = array();
        $source = self::$VALID_TARGETS[$this->source];

        foreach ($source as $target) {

            if (! isset($state[$target])) {
                continue;
            }

            $indexes = array_keys($state[$target]);
            if ($baseGroup) {
                $baseGroup->setQuery($this);
                $idx = array_keys($state[$target]);
                $indexes = $baseGroup->filter($state[$target], $idx );
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
     * Order the given result set
     *
     * @param array $indices        The result set of the query that should be ordered
     */
    private function orderIndices(array &$indices)
    {
        if (!empty($this->orderColumns)) {
            foreach ($indices as $type => &$subindices) {
                $this->currentType = $type;
                usort($subindices, array($this, 'orderResult'));
            }
        }
    }

    /**
     * Start a query
     *
     * This is just a dummy function to allow a more convenient syntax
     *
     * @return self         Fluent interface
     */
    public function select()
    {
        return $this;
    }

    /**
     * Order implementation called by usort
     *
     * @param String $a     The left object index
     * @param Strinv $b     The right object index
     * @return int          0, 1 or -1, see usort for detail
     */
    private function orderResult($a, $b)
    {
        $o1 = $this->ds->getObjectByName($this->currentType, $a);
        $o2 = $this->ds->getObjectByName($this->currentType, $b);
        $result = 0;

        foreach ($this->orderColumns as &$col) {
            if (is_array($col[0])) {
                // sort by function
                $result += $col[1] * strnatcasecmp(
                    $col[0][0]->$col[0][1]($o1),
                    $col[0][0]->$col[0][1]($o2)
                );
            } else {
                $result += $col[1] * strnatcasecmp($o1->{$col[0]}, $o2->{$col[0]});
            }
        }
        return $result;
    }

    /**
     * Limit the given resultset
     *
     * @param array $indices    The filtered, ordered indices
     */
    private function limitIndices(array &$indices)
    {
        foreach ($indices as $type => $subindices) {
            $indices[$type] = array_slice($subindices, $this->getOffset(), $this->getLimit());
        }
    }

    /**
     * Register the given function for grouping the result
     *
     * @param String $fn    The function to use for grouping
     * @param Object $scope An optional scope to use instead of $this
     *
     * @return self         Fluent interface
     */
    public function groupByFunction($fn, $scope = null)
    {
        $this->groupByFn = array($scope ? $scope : $this, $fn);
        return $this;
    }

    /**
     * Group by the given column
     *
     * @param  array|string $columns    The columns to use for grouping
     * @return self                     Fluent interface
     * @see    Query::columnGroupFn()   The implementation used for grouping
     */
    public function groupByColumns($columns)
    {
        if (!is_array($columns)) {
            $columns = array($columns);
        }
        $this->groupColumns = $columns;
        $this->groupByFn = array($this, 'columnGroupFn');
        return $this;
    }

    /**
     * The internal handler function used by the group function
     *
     * @param array $indices        The indices to group
     * @return array                The grouped result set
     */
    private function columnGroupFn(array &$indices)
    {
        $cols = $this->groupColumns;
        $result = array();
        foreach ($indices as $type => $subindices) {
            foreach ($subindices as $objectIndex) {
                $r = $this->ds->getObjectByName($type, $objectIndex);
                $hash = '';
                $cols = array();
                foreach ($this->groupColumns as $col) {
                    $hash = md5($hash . $r->$col);
                    $cols[$col] = $r->$col;
                }
                if (!isset($result[$hash])) {
                    $result[$hash] = (object)array(
                        'columns' => (object)$cols,
                        'count' => 0
                    );
                }
                $result[$hash]->count++;
            }
        }
        return array_values($result);
    }

    /**
     * Query Filter, Order, Group, Limit and return the result set
     *
     * @return array    The resultset matching this query
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
        $state = $this->ds->getState();

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
            $query = $parser->treeToQuery($this->getFilter(), $this);
            $this->setQueryFilter($query);
        }

    }

    /**
     * Return only the first row fetched from the result set
     *
     * @return MonitoringObjectList     The monitoring object matching this query
     */
    public function fetchRow()
    {
        $rs = $this->fetchAll();
        $rs->rewind();
        return $rs->current();
    }

    /**
     * Fetch the result as an associative array using the first column as the key and the second as the value
     *
     * @return array        An associative array with the result
     * @throws \Exception   If no attributes are defined
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
     * Fetch all results
     *
     * @return MonitoringObjectList     An MonitoringObjectList wrapping the given resultset
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
     * Return the value of the first column for the first row fetched from the result set
     */
    public function fetchOne()
    {
        throw new ProgrammingError('Statusdat/Query::fetchOne not yet implemented');
    }

    /**
     * Count the number of results
     *
     * @return int
     */
    public function count()
    {
        $q = clone $this;
        $q->limit(null, null);
        return count($q->fetchAll());
    }
}
