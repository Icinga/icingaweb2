<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

use Iterator;
use IteratorAggregate;
use Zend_Paginator;
use Icinga\Application\Icinga;
use Icinga\Application\Benchmark;
use Icinga\Data\Filter\Filter;
use Icinga\Exception\IcingaException;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Paginator\Adapter\QueryAdapter;

class SimpleQuery implements QueryInterface, Queryable, Iterator
{
    /**
     * Query data source
     *
     * @var mixed
     */
    protected $ds;

    /**
     * This query's iterator
     *
     * @var Iterator
     */
    protected $iterator;

    /**
     * The current position of this query's iterator
     *
     * @var int
     */
    protected $iteratorPosition;

    /**
     * The target you are going to query
     *
     * @var mixed
     */
    protected $target;

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
     * The columns and their aliases flipped in order to handle aliased sort columns
     *
     * Supposed to be used and populated by $this->compare *only*.
     *
     * @var array
     */
    protected $flippedColumns;

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

    /**
     * Whether to peek ahead for more results
     *
     * @var bool
     */
    protected $peekAhead;

    /**
     * Whether the query did not yield all available results
     *
     * @var bool
     */
    protected $hasMore;

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
    protected function init()
    {
    }

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
     * Return the current position of this query's iterator
     *
     * @return  int
     */
    public function getIteratorPosition()
    {
        return $this->iteratorPosition;
    }

    /**
     * Start or rewind the iteration
     */
    public function rewind()
    {
        if ($this->iterator === null) {
            $iterator = $this->ds->query($this);
            if ($iterator instanceof IteratorAggregate) {
                $this->iterator = $iterator->getIterator();
            } else {
                $this->iterator = $iterator;
            }
        }

        $this->iterator->rewind();
        $this->iteratorPosition = null;
        Benchmark::measure('Query result iteration started');
    }

    /**
     * Fetch and return the current row of this query's result
     *
     * @return  object
     */
    public function current()
    {
        return $this->iterator->current();
    }

    /**
     * Return whether the current row of this query's result is valid
     *
     * @return  bool
     */
    public function valid()
    {
        $valid = $this->iterator->valid();
        if ($valid && $this->peekAhead && $this->hasLimit() && $this->iteratorPosition + 1 === $this->getLimit()) {
            $this->hasMore = true;
            $valid = false; // We arrived at the last result, which is the requested extra row, so stop the iteration
        } elseif (! $valid) {
            $this->hasMore = false;
        }

        if (! $valid) {
            Benchmark::measure('Query result iteration finished');
            return false;
        } elseif ($this->iteratorPosition === null) {
            $this->iteratorPosition = 0;
        }

        return true;
    }

    /**
     * Return the key for the current row of this query's result
     *
     * @return  mixed
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * Advance to the next row of this query's result
     */
    public function next()
    {
        $this->iterator->next();
        $this->iteratorPosition += 1;
    }

    /**
     * Choose a table and the columns you are interested in
     *
     * Query will return all available columns if none are given here.
     *
     * @param   mixed   $target
     * @param   array   $fields
     *
     * @return  $this
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

    /**
     * Compare $a with $b based on this query's sort rules and column aliases
     *
     * @param   object  $a
     * @param   object  $b
     * @param   int     $orderIndex
     *
     * @return  int
     */
    public function compare($a, $b, $orderIndex = 0)
    {
        if (! array_key_exists($orderIndex, $this->order)) {
            return 0; // Last column to sort reached, rows are considered being equal
        }

        if ($this->flippedColumns === null) {
            $this->flippedColumns = array_flip($this->columns);
        }

        $column = $this->order[$orderIndex][0];
        if (array_key_exists($column, $this->flippedColumns)) {
            $column = $this->flippedColumns[$column];
        }

        // TODO: throw Exception if column is missing
        //$res = strnatcmp(strtolower($a->$column), strtolower($b->$column));
        $result = @strcmp(strtolower($a->$column), strtolower($b->$column));
        if ($result === 0) {
            return $this->compare($a, $b, ++$orderIndex);
        }

        $direction = $this->order[$orderIndex][1];
        if ($direction === self::SORT_ASC) {
            return $result;
        } else {
            return $result * -1;
        }
    }

    /**
     * Clear the order if any
     *
     * @return $this
     */
    public function clearOrder()
    {
        $this->order = array();
        return $this;
    }

    /**
     * Whether an order is set
     *
     * @return bool
     */
    public function hasOrder()
    {
        return ! empty($this->order);
    }

    /**
     * Get the order
     *
     * @return array
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set whether this query should peek ahead for more results
     *
     * Enabling this causes the current query limit to be increased by one. The potential extra row being yielded will
     * be removed from the result set. Note that this only applies when fetching multiple results of limited queries.
     *
     * @return  $this
     */
    public function peekAhead($state = true)
    {
        $this->peekAhead = (bool) $state;
        return $this;
    }

    /**
     * Return whether this query did not yield all available results
     *
     * @return  bool
     *
     * @throws  ProgrammingError    In case the query did not run yet
     */
    public function hasMore()
    {
        if ($this->hasMore === null) {
            throw new ProgrammingError('Query did not run. Cannot determine whether there are more results.');
        }

        return $this->hasMore;
    }

    /**
     * Return whether this query will or has yielded any result
     *
     * @return  bool
     */
    public function hasResult()
    {
        return $this->iteratorPosition !== null || $this->fetchRow() !== false;
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
        return $this->limitCount !== null && $this->limitCount > 0;
    }

    /**
     * Get the limit if any
     *
     * @return int|null
     */
    public function getLimit()
    {
        return $this->peekAhead && $this->hasLimit() ? $this->limitCount + 1 : $this->limitCount;
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
     *
     * @deprecated      Use Icinga\Web\Controller::setupPaginationControl() and/or Icinga\Web\Widget\Paginator instead
     */
    public function paginate($itemsPerPage = null, $pageNumber = null)
    {
        trigger_error(
            'SimpleQuery::paginate() is deprecated. Use Icinga\Web\Controller::setupPaginationControl()'
            . ' and/or Icinga\Web\Widget\Paginator instead',
            E_USER_DEPRECATED
        );

        if ($itemsPerPage === null || $pageNumber === null) {
            // Detect parameters from request
            $request = Icinga::app()->getRequest();
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
        Benchmark::measure('Fetching all results started');
        $results = $this->ds->fetchAll($this);
        Benchmark::measure('Fetching all results finished');

        if ($this->peekAhead && $this->hasLimit() && count($results) === $this->getLimit()) {
            $this->hasMore = true;
            array_pop($results);
        } else {
            $this->hasMore = false;
        }

        return $results;
    }

    /**
     * Fetch the first row of the result set
     *
     * @return mixed
     */
    public function fetchRow()
    {
        Benchmark::measure('Fetching one row started');
        $row = $this->ds->fetchRow($this);
        Benchmark::measure('Fetching one row finished');
        return $row;
    }

    /**
     * Fetch the first column of all rows of the result set as an array
     *
     * @return  array
     */
    public function fetchColumn()
    {
        Benchmark::measure('Fetching one column started');
        $values = $this->ds->fetchColumn($this);
        Benchmark::measure('Fetching one column finished');

        if ($this->peekAhead && $this->hasLimit() && count($values) === $this->getLimit()) {
            $this->hasMore = true;
            array_pop($values);
        } else {
            $this->hasMore = false;
        }

        return $values;
    }

    /**
     * Fetch the first column of the first row of the result set
     *
     * @return string
     */
    public function fetchOne()
    {
        Benchmark::measure('Fetching one value started');
        $value = $this->ds->fetchOne($this);
        Benchmark::measure('Fetching one value finished');
        return $value;
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
        Benchmark::measure('Fetching pairs started');
        $pairs = $this->ds->fetchPairs($this);
        Benchmark::measure('Fetching pairs finished');

        if ($this->peekAhead && $this->hasLimit() && count($pairs) === $this->getLimit()) {
            $this->hasMore = true;
            array_pop($pairs);
        } else {
            $this->hasMore = false;
        }

        return $pairs;
    }

    /**
     * Count all rows of the result set, ignoring limit and offset
     *
     * @return  int
     */
    public function count()
    {
        $query = clone $this;
        $query->limit(0, 0);
        Benchmark::measure('Counting all results started');
        $count = $this->ds->count($query);
        Benchmark::measure('Counting all results finished');
        return $count;
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
        $this->flippedColumns = null; // Reset, due to updated columns
        return $this;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Deep clone self::$filter
     */
    public function __clone()
    {
        $this->filter = clone $this->filter;
    }
}
