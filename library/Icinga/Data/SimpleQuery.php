<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

use Icinga\Application\Icinga;
use Icinga\Data\Filter\Filter;
use Icinga\Web\Paginator\Adapter\QueryAdapter;
use Zend_Paginator;
use Exception;
use Icinga\Exception\IcingaException;

class SimpleQuery implements QueryInterface, Queryable
{
    /**
     * Query data source
     *
     * @var mixed
     */
    protected $ds;

    /**
     * The table you are going to query
     */
    protected $table;

    /**
     * The columns you asked for
     *
     * All columns if null, no column if empty??? Alias handling goes here!
     *
     * @var array
     */
    protected $desiredColumns = array();

    /**
     * The columns you are interested in
     *
     * All columns if null, no column if empty??? Alias handling goes here!
     *
     * @var array
     */
    protected $columns = array();

    /**
     * The columns you're using to sort the query result
     *
     * @var array
     */
    protected $order = array();

    /**
     * Number of rows to return
     *
     * @var int
     */
    protected $limitCount;

    /**
     * Result starts with this row
     *
     * @var int
     */
    protected $limitOffset;

    protected $filter;

    /**
     * Constructor
     *
     * @param mixed $ds
     */
    public function __construct($ds, $columns = null)
    {
        $this->ds = $ds;
        $this->filter = Filter::matchAll();
        if ($columns !== null) {
            $this->desiredColumns = $columns;
        }
        $this->init();
        if ($this->desiredColumns !== null) {
            $this->columns($this->desiredColumns);
        }
    }

    /**
     * Initialize query
     *
     * Overwrite this instead of __construct (it's called at the end of the construct) to
     * implement custom initialization logic on construction time
     */
    protected function init() {}

    /**
     * Get the data source
     *
     * @return mixed
     */
    public function getDatasource()
    {
        return $this->ds;
    }

    /**
     * Choose a table and the colums you are interested in
     *
     * Query will return all available columns if none are given here
     *
     * @return $this
     */
    public function from($target, array $fields = null)
    {
        $this->target = $target;
        if ($fields !== null) {
            $this->columns($fields);
        }
        return $this;
    }

    /**
     * Add a where condition to the query by and
     *
     * The syntax of the condition and valid values are defined by the concrete backend-specific query implementation.
     *
     * @param   string  $condition
     * @param   mixed   $value
     *
     * @return  $this
     */
    public function where($condition, $value = null)
    {
        // TODO: more intelligence please
        $this->filter->addFilter(Filter::expression($condition, '=', $value));
        return $this;
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function applyFilter(Filter $filter)
    {
        return $this->addFilter($filter);
    }

    public function addFilter(Filter $filter)
    {
        $this->filter->addFilter($filter);
        return $this;
    }

    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;
        return $this;
    }

    public function setOrderColumns(array $orderColumns)
    {
        throw new IcingaException('This function does nothing and will be removed');
    }

    /**
     * Split order field into its field and sort direction
     *
     * @param   string  $field
     *
     * @return  array
     */
    public function splitOrder($field)
    {
        $fieldAndDirection = explode(' ', $field, 2);
        if (count($fieldAndDirection) === 1) {
            $direction = null;
        } else {
            $field = $fieldAndDirection[0];
            $direction = (strtoupper(trim($fieldAndDirection[1])) === 'DESC') ?
                Sortable::SORT_DESC : Sortable::SORT_ASC;
        }
        return array($field, $direction);
    }

    /**
     * Sort result set by the given field (and direction)
     *
     * Preferred usage:
     * <code>
     * $query->order('field, 'ASC')
     * </code>
     *
     * @param  string   $field
     * @param  string   $direction
     *
     * @return $this
     */
    public function order($field, $direction = null)
    {
        if ($direction === null) {
            list($field, $direction) = $this->splitOrder($field);
            if ($direction === null) {
                $direction = Sortable::SORT_ASC;
            }
        } else {
            switch (($direction = strtoupper($direction))) {
                case Sortable::SORT_ASC:
                case Sortable::SORT_DESC:
                    break;
                default:
                    $direction = Sortable::SORT_ASC;
                    break;
            }
        }
        $this->order[] = array($field, $direction);
        return $this;
    }

    public function compare($a, $b, $col_num = 0)
    {
        // Last column to sort reached, rows are considered being equal
        if (! array_key_exists($col_num, $this->order)) {
            return 0;
        }
        $col = $this->order[$col_num][0];
        $dir = $this->order[$col_num][1];
// TODO: throw Exception if column is missing
        //$res = strnatcmp(strtolower($a->$col), strtolower($b->$col));
        $res = @strcmp(strtolower($a->$col), strtolower($b->$col));
        if ($res === 0) {
//            return $this->compare($a, $b, $col_num++);

            if (array_key_exists(++$col_num, $this->order)) {
                return $this->compare($a, $b, $col_num);
            } else {
                return 0;
            }

        }

        if ($dir === self::SORT_ASC) {
            return $res;
        } else {
            return $res * -1;
        }
    }

    /**
     * Whether an order is set
     *
     * @return bool
     */
    public function hasOrder()
    {
        return !empty($this->order);
    }

    /**
     * Get the order if any
     *
     * @return array|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set a limit count and offset to the query
     *
     * @param   int $count  Number of rows to return
     * @param   int $offset Start returning after this many rows
     *
     * @return  $this
     */
    public function limit($count = null, $offset = null)
    {
        $this->limitCount = $count !== null ? (int) $count : null;
        $this->limitOffset = (int) $offset;
        return $this;
    }

    /**
     * Whether a limit is set
     *
     * @return bool
     */
    public function hasLimit()
    {
        return $this->limitCount !== null;
    }

    /**
     * Get the limit if any
     *
     * @return int|null
     */
    public function getLimit()
    {
        return $this->limitCount;
    }

    /**
     * Whether an offset is set
     *
     * @return bool
     */
    public function hasOffset()
    {
        return $this->limitOffset > 0;
    }

    /**
     * Get the offset if any
     *
     * @return int|null
     */
    public function getOffset()
    {
        return $this->limitOffset;
    }

    /**
     * Paginate data
     *
     * Auto-detects pagination parameters from request when unset
     *
     * @param   int $itemsPerPage   Number of items per page
     * @param   int $pageNumber     Current page number
     *
     * @return  Zend_Paginator
     */
    public function paginate($itemsPerPage = null, $pageNumber = null)
    {
        if ($itemsPerPage === null || $pageNumber === null) {
            // Detect parameters from request
            $request = Icinga::app()->getFrontController()->getRequest();
            if ($itemsPerPage === null) {
                $itemsPerPage = $request->getParam('limit', 25);
            }
            if ($pageNumber === null) {
                $pageNumber = $request->getParam('page', 0);
            }
        }
        $this->limit($itemsPerPage, $pageNumber * $itemsPerPage);
        $paginator = new Zend_Paginator(new QueryAdapter($this));
        $paginator->setItemCountPerPage($itemsPerPage);
        $paginator->setCurrentPageNumber($pageNumber);
        return $paginator;
    }

    /**
     * Retrieve an array containing all rows of the result set
     *
     * @return array
     */
    public function fetchAll()
    {
        return $this->ds->fetchAll($this);
    }

    /**
     * Fetch the first row of the result set
     *
     * @return mixed
     */
    public function fetchRow()
    {
        return $this->ds->fetchRow($this);
    }

    /**
     * Fetch a column of all rows of the result set as an array
     *
     * @param   int $columnIndex Index of the column to fetch
     *
     * @return  array
     */
    public function fetchColumn($columnIndex = 0)
    {
        return $this->ds->fetchColumn($this, $columnIndex);
    }

    /**
     * Fetch the first column of the first row of the result set
     *
     * @return string
     */
    public function fetchOne()
    {
        return $this->ds->fetchOne($this);
    }

    /**
     * Fetch all rows of the result set as an array of key-value pairs
     *
     * The first column is the key, the second column is the value.
     *
     * @return array
     */
    public function fetchPairs()
    {
        return $this->ds->fetchPairs($this);
    }

    /**
     * Count all rows of the result set
     *
     * @return int
     */
    public function count()
    {
        return $this->ds->count($this);
    }

    /**
     * Set columns
     *
     * @param   array $columns
     *
     * @return  $this
     */
    public function columns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    public function getColumns()
    {
        return $this->columns;
    }
}
