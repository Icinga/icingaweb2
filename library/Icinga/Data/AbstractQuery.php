<?php

namespace Icinga\Data;

use Icinga\Exception;

abstract class AbstractQuery implements QueryInterface
{
    /**
     * Sort ascending
     */
    const SORT_ASC  = 1;

    /**
     * Sort descending
     */
    const SORT_DESC = -1;

    /**
     * Query data source
     *
     * @var DatasourceInterface
     */
    protected $ds;

    /**
     * The table you are going to query
     */
    protected $table;

    /**
     * The columns you are interested in. All columns if empty
     */
    protected $columns = array();

    /**
     * A list of filters
     */
    protected $filters = array();

    /**
     * The columns you're using to sort the query result
     */
    protected $order_columns = array();

    /**
     * Return not more than that many rows
     */
    protected $limit_count;

    /**
     * Result starts with this row
     */
    protected $limit_offset;

    /**
     * Constructor
     *
     * @param DatasourceInterface $ds Your data source
     */
    public function __construct(DatasourceInterface $ds, $columns = null)
    {
        $this->ds = $ds;

        if ($columns === null) {
            $columns = $this->getDefaultColumns();
        }
        if ($columns !== null) {
            $this->columns($columns);
        }

        $this->init();
    }

    public function addColumn($name, $alias = null)
    {
        // TODO: Fail if adding column twice, but allow same col with new alias
        if ($alias === null) {
            $this->columns[] = $name;
        } else {
            $this->columns[$alias] = $name;
        }
        return $this;
    }

    public function getDatasource()
    {
        return $this->ds;
    }

    protected function getDefaultColumns()
    {
        return null;
    }

    /**
     * Choose a table and the colums you are interested in
     *
     * Query will return all available columns if none are given here
     *
     * @return self
     */
    public function from($table, $columns = null)
    {
        $this->table = $table;
        if ($columns !== null) {
            $this->columns($columns);
        } else {
            // TODO: Really?
            $this->columns = $this->getDefaultColumns();
        }
        return $this;
    }

    public function columns($columns)
    {
        if (is_array($columns)) {
            $this->columns = $columns;
        } else {
            $this->columns = array($columns);
        }
        return $this;
    }

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
    public function where($key, $val = null)
    {
        $this->filters[] = array($key, $val);
        return $this;
    }

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
    public function order($col, $dir = null)
    {
        if ($dir === null) {
            if (($pos = strpos($col, ' ')) === false) {
                $dir = $this->getDefaultSortDir($col);
            } else {
                $dir = strtoupper(substr($col, $pos + 1));
                if ($dir === 'DESC') {
                    $dir = self::SORT_DESC;
                } else {
                    $dir = self::SORT_ASC;
                }
                $col = substr($col, 0, $pos);
            }
        } else {
            if ($dir === self::SORT_DESC || strtoupper($dir) === 'DESC') {
                $dir = self::SORT_DESC;
            } else {
                $dir = self::SORT_ASC;
            }
        }
        $this->order_columns[] = array($col, $dir);
        return $this;
    }

    protected function getDefaultSortDir($col)
    {
        return self::SORT_ASC;
    }

    /**
     * Limit the result set
     *
     * @param int $count  Return not more than that many rows
     * @param int $offset Result starts with this row
     *
     * @return self
     */
    // Nur wenn keine stats, sonst im RAM!!
    // Offset gibt es nicht, muss simuliert werden
    public function limit($count = null, $offset = null)
    {
        if (! preg_match('~^\d+~', $count . $offset)) {
            throw new Exception\ProgrammingError(
                sprintf(
                    'Got invalid limit: %s, %s',
                    $count,
                    $offset
                )
            );
        }
        $this->limit_count  = (int) $count;
        $this->limit_offset = (int) $offset;
        return $this;
    }

    /**
     * Wheter at least one order column has been applied to this Query
     *
     * @return bool
     */
    public function hasOrder()
    {
        return ! empty($this->order_columns);
    }

    /**
     * Wheter a limit has been applied to this Query
     *
     * @return bool
     */
    public function hasLimit()
    {
        return $this->limit_count !== null;
    }

    /**
     * Wheter a starting offset been applied to this Query
     *
     * @return bool
     */
    public function hasOffset()
    {
        return $this->limit_offset > 0;
    }

    /**
     * Get the query limit
     *
     * @return int|null
     */
    public function getLimit()
    {
        return $this->limit_count;
    }

    /**
     * Get the query starting offset
     *
     * @return int|null
     */
    public function getOffset()
    {
        return $this->limit_offset;
    }

    /**
     * Get the columns that have been asked for with this query
     *
     * @return array
     */
    public function listColumns()
    {
        return $this->columns;
    }

    /**
     * Get the filters that have been applied to this query
     *
     * @return array
     */
    public function listFilters()
    {
        return $this->filters;
    }

    /**
     * Extend this function for things that should happen at construction time
     */
    protected function init()
    {
    }

    /**
     * Extend this function for things that should happen before query execution
     */
    protected function finish()
    {
    }

    /**
     * Total result size regardless of limit and offset
     *
     * @return int
     */
    public function count()
    {
        return $this->ds->count($this);
    }

    /**
     * Fetch result as an array of objects
     *
     * @return array
     */
    public function fetchAll()
    {
        return $this->ds->fetchAll($this);
    }

    /**
     * Fetch first result row
     *
     * @return object
     */
    public function fetchRow()
    {
        return $this->ds->fetchRow($this);
    }

    /**
     * Fetch first result column
     *
     * @return array
     */
    public function fetchColumn()
    {
        return $this->ds->fetchColumn($this);
    }

    /**
     * Fetch first column value from first result row
     *
     * @return mixed
     */
    public function fetchOne()
    {
        return $this->ds->fetchOne($this);
    }

    /**
     * Fetch result as a key/value pair array
     *
     * @return array
     */
    public function fetchPairs()
    {
        return $this->ds->fetchPairs($this);
    }

    /**
     * Return a pagination adapter for this query
     *
     * @return \Zend_Paginator
     */
    public function paginate($limit = null, $page = null)
    {
        $this->finish();
        if ($page === null && $limit === null) {
            $request = \Zend_Controller_Front::getInstance()->getRequest();

            if ($page === null) {
                $page = $request->getParam('page', 0);
            }

            if ($limit === null) {
                $limit = $request->getParam('limit', 20);
            }
        }
        $this->limit($limit, $page * $limit);

        $paginator = new \Zend_Paginator(
            new \Icinga\Web\Paginator\Adapter\QueryAdapter($this)
        );

        $paginator->setItemCountPerPage($limit);
        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Destructor. Remove $ds, just to be on the safe side
     */
    public function __destruct()
    {
        unset($this->ds);
    }
}
