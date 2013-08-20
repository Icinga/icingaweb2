<?php

namespace Icinga\Data;

use Countable;

interface QueryInterface extends Countable
{
    /**
     * Constructor
     *
     * @param DatasourceInterface $ds Your data source
     */
    public function __construct(DatasourceInterface $ds, $columns = null);

    public function getDatasource();

    /**
     * Choose a table and the colums you are interested in
     *
     * Query will return all available columns if none are given here
     *
     * @return self
     */
    public function from($table, $columns = null);

    public function columns($columns);

    /**
     * Use once or multiple times to filter result set
     *
     * Multiple where calls will be combined by a logical AND operation
     *
     * @param string $key Column or backend-specific search expression
     * @param string $val Search value, must be escaped automagically
     *
     * @return self
     */
    public function where($key, $val = null);

    /**
     * Sort query result by the given column name
     *
     * Sort direction can be ascending (self::SORT_ASC, being the default)
     * or descending (self::SORT_DESC).
     *
     * Preferred usage:
     * <code>
     * $query->sort('column_name ASC')
     * </code>
     *
     * @param  string $col Column, may contain direction separated by space
     * @param  int    $dir Sort direction
     *
     * @return self
     */
    public function order($col, $dir = null);

    /**
     * Limit the result set
     *
     * @param int $count  Return not more than that many rows
     * @param int $offset Result starts with this row
     *
     * @return self
     */
    public function limit($count = null, $offset = null);

    /**
     * Wheter at least one order column has been applied to this Query
     *
     * @return bool
     */
    public function hasOrder();

    /**
     * Wheter a limit has been applied to this Query
     *
     * @return bool
     */
    public function hasLimit();

    /**
     * Wheter a starting offset been applied to this Query
     *
     * @return bool
     */
    public function hasOffset();

    /**
     * Get the query limit
     *
     * @return int|null
     */
    public function getLimit();

    /**
     * Get the query starting offset
     *
     * @return int|null
     */
    public function getOffset();

    /**
     * Get the columns that have been asked for with this query
     *
     * @return array
     */
    public function listColumns();

    public function getColumns();

    /**
     * Get the filters that have been applied to this query
     *
     * @return array
     */
    public function listFilters();


    /**
     * Fetch result as an array of objects
     *
     * @return array
     */
    public function fetchAll();

    /**
     * Fetch first result row
     *
     * @return object
     */
    public function fetchRow();

    /**
     * Fetch first result column
     *
     * @return array
     */
    public function fetchColumn();

    /**
     * Fetch first column value from first result row
     *
     * @return mixed
     */
    public function fetchOne();

    /**
     * Fetch result as a key/value pair array
     *
     * @return array
     */
    public function fetchPairs();

    /**
     * Return a pagination adapter for this query
     *
     * @return \Zend_Paginator
     */
    public function paginate($limit = null, $page = null);
}

