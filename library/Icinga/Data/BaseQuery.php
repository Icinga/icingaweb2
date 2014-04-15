<?php

namespace Icinga\Data;

use Countable;
use Zend_Controller_Front;
use Zend_Paginator;
use Icinga\Web\Paginator\Adapter\QueryAdapter;

abstract class BaseQuery implements Browsable, Fetchable, Filterable, Limitable, Queryable, Sortable, Countable
{
    /**
     * Query data source
     *
     * @var mixed
     */
    protected $ds;

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
     * Constructor
     *
     * @param mixed $ds
     */
    public function __construct($ds)
    {
        $this->ds = $ds;
        $this->init();
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
     * Add a where condition to the query by and
     *
     * The syntax of the condition and valid values are defined by the concrete backend-specific query implementation.
     *
     * @param   string  $condition
     * @param   mixed   $value
     *
     * @return  self
     */
    abstract public function where($condition, $value = null);

    /**
     * Add a where condition to the query by or
     *
     * The syntax of the condition and valid values are defined by the concrete backend-specific query implementation.
     *
     * @param   string  $condition
     * @param   mixed   $value
     *
     * @return  self
     */
    abstract public function orWhere($condition, $value = null);

    public function setOrderColumns(array $orderColumns)
    {
        throw new \Exception('This function does nothing and will be removed');
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
     * @param  int      $direction
     *
     * @return self
     */
    public function order($field, $direction = null)
    {
        if ($direction === null) {
            $fieldAndDirection = explode(' ', $field, 2);
            if (count($fieldAndDirection) === 1) {
                $direction = self::SORT_ASC;
            } else {
                $field = $fieldAndDirection[0];
                $direction = (strtoupper(trim($fieldAndDirection[1])) === 'DESC') ?
                    Sortable::SORT_DESC : Sortable::SORT_ASC;
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
     * @return  self
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
            $request = Zend_Controller_Front::getInstance()->getRequest();
            if ($itemsPerPage === null) {
                $itemsPerPage = $request->getParam('limit', 20);
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
     * @return  self
     */
    abstract public function columns(array $columns);
}
