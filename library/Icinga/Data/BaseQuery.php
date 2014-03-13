<?php

namespace Icinga\Data;

use Icinga\Logger\Logger;
use Icinga\Exception;
use Icinga\Filter\Filterable;
use Icinga\Filter\Query\Node;
use Icinga\Filter\Query\Tree;
use Zend_Paginator;
use Icinga\Web\Paginator\Adapter\QueryAdapter;

abstract class BaseQuery implements Filterable
{
    /**
     * Sort ascending
     */
    const SORT_ASC = 1;

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
     * The target of this query
     * @var string
     */
    protected $table;

    /**
     * The columns of the target that should be returned
     * @var array
     */
    private $columns;

    /**
     * The columns you're using to sort the query result
     * @var array
     */
    private $orderColumns = array();

    /**
     * Return not more than that many rows
     * @var int
     */
    private $limitCount;

    /**
     * Result starts with this row
     * @var int
     */
    private $limitOffset;

    /**
     * Whether its a distinct query or not
     *
     * @var bool
     */
    private $distinct = false;

    /**
     * The backend independent filter to use for this query
     *
     * @var Tree
     */
    private $filter;

    /**
     * Constructor
     *
     * @param DatasourceInterface $ds Your data source
     */
    public function __construct(DatasourceInterface $ds, array $columns = null)
    {
        $this->ds = $ds;
        $this->columns = $columns;
        $this->clearFilter();
        $this->init();

    }

    public function getDatasource()
    {
        return $this->ds;
    }


    public function setColumns(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * Define the target and attributes for this query
     *
     * The Query will return the default attribute the attributes parameter is omitted
     *
     * @param String $target    The target of this query (tablename, objectname, depends on the concrete implementation)
     * @param array $columns   An optional array of columns to select, if none are given the default
     *                          columnset is returned
     *
     * @return self             Fluent interface
     */
    public function from($target, array $attributes = null)
    {
        $this->table = $target;
        if ($attributes !== null) {
            $this->columns = $attributes;
        }
        return $this;
    }

    /**
     * Add a filter expression to be applied on this query.
     *
     * This is an alias to andWhere()
     * The syntax of the expression and valid parameters are to be defined by the concrete
     * backend-specific query implementation.
     *
     * @param string $expression    Implementation specific search expression
     * @param mixed $parameters    Implementation specific search value to use for query placeholders
     *
     * @return self                 Fluent Interface
     * @see BaseQuery::andWhere()   This is an alias to andWhere()
     */
    public function where($expression, $parameters = null)
    {
        return $this->andWhere($expression, $parameters);
    }

    /**
     * Add an mandatory filter expression to be applied on this query
     *
     * The syntax of the expression and valid parameters are to be defined by the concrete
     * backend-specific query implementation.
     *
     * @param string $expression    Implementation specific search expression
     * @param mixed $parameters    Implementation specific search value to use for query placeholders
     * @return self                 Fluent interface
     */
    public function andWhere($expression, $parameters = null)
    {
        $node = $this->parseFilterExpression($expression, $parameters);
        if ($node === null) {
            Logger::debug('Ignoring invalid filter expression: %s (params: %s)', $expression, $parameters);
            return $this;
        }
        $this->filter->insert(Node::createAndNode());
        $this->filter->insert($node);
        return $this;
    }

    /**
     * Add an lower priority filter expression to be applied on this query
     *
     * The syntax of the expression and valid parameters are to be defined by the concrete
     * backend-specific query implementation.
     *
     * @param string $expression    Implementation specific search expression
     * @param mixed $parameters    Implementation specific search value to use for query placeholders
     * @return self                 Fluent interface
     */
    public function orWhere($expression, $parameters = null)
    {
        $node = $this->parseFilterExpression($expression, $parameters);
        if ($node === null) {
            Logger::debug('Ignoring invalid filter expression: %s (params: %s)', $expression, $parameters);
            return $this;
        }
        $this->filter->insert(Node::createOrNode());
        $this->filter->insert($node);
        return $this;
    }

    /**
     * Determine whether the given field is a valid filter target
     *
     * The base implementation always returns true, overwrite it in concrete backend-specific
     * implementations
     *
     * @param   String $field       The field to test for being filterable
     * @return  bool                True if the field can be filtered, otherwise false
     */
    public function isValidFilterTarget($field)
    {
        return true;
    }

    /**
     * Return the internally used field name for the given alias
     *
     * The base implementation just returns the given field, overwrite it in concrete backend-specific
     * implementations
     *
     * @param   String $field       The field to test for being filterable
     * @return  bool                True if the field can be filtered, otherwise false
     */
    public function getMappedField($field)
    {
        return $field;
    }

    /**
     * Add a filter to this query
     *
     * This is the implementation for the Filterable, use where instead
     *
     * @param $filter
     */
    public function addFilter($filter)
    {
        if (is_string($filter)) {
            $this->addFilter(call_user_func_array(array($this, 'parseFilterExpression'), func_get_args()));
        } elseif ($filter instanceof Node) {
            $this->filter->insert($filter);
        }
    }

    public function setFilter(Tree $filter)
    {
        $this->filter = $filter;
    }

    /**
     * Return all default columns
     *
     * @return array    An array of default columns to use when none are selected
     */
    public function getDefaultColumns()
    {
        return array();
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
     * @param  string $columnOrAlias Column, may contain direction separated by space
     * @param  int $dir Sort direction
     *
     * @return BaseQuery
     */
    public function order($columnOrAlias, $dir = null)
    {
        if ($dir === null) {
            $colDirPair = explode(' ', $columnOrAlias, 2);
            if (count($colDirPair) === 1) {
                $dir = $this->getDefaultSortDir($columnOrAlias);
            } else {
                $dir = $colDirPair[1];
                $columnOrAlias = $colDirPair[0];
            }
        }

        $dir = (strtoupper(trim($dir)) === 'DESC') ? self::SORT_DESC : self::SORT_ASC;

        $this->orderColumns[] = array($columnOrAlias, $dir);
        return $this;
    }

    /**
     * Determine the default sort direction constant for the given column
     *
     * @param  String $col      The column to get the sort direction for
     * @return int              Either SORT_ASC or SORT_DESC
     */
    protected function getDefaultSortDir($col)
    {
        return self::SORT_ASC;
    }

    /**
     * Limit the result set
     *
     * @param int $count    The numeric maximum limit to apply on the query result
     * @param int $offset   The offset to use for the result set
     *
     * @return BaseQuery
     */
    public function limit($count = null, $offset = null)
    {
        $this->limitCount = $count !== null ? intval($count) : null;
        $this->limitOffset = intval($offset);

        return $this;
    }

    /**
     * Return only distinct results
     *
     * @param   bool    $distinct   Whether the query should be distinct or not
     *
     * @return  BaseQuery
     */
    public function distinct($distinct = true)
    {
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * Determine whether this query returns only distinct results
     *
     * @return  bool    True in case its a distinct query otherwise false
     */
    public function isDistinct()
    {
        return $this->distinct;
    }

    /**
     * Determine whether this query will be ordered explicitly
     *
     * @return bool     True when an order column has been set
     */
    public function hasOrder()
    {
        return !empty($this->orderColumns);
    }

    /**
     * Determine whether this query will be limited explicitly
     *
     * @return bool     True when an limit count has been set, otherwise false
     */
    public function hasLimit()
    {
        return $this->limitCount !== null;
    }

    /**
     * Determine whether an offset is set or not
     *
     * @return bool     True when an offset > 0 is set
     */
    public function hasOffset()
    {
        return $this->limitOffset > 0;
    }

    /**
     * Get the query limit
     *
     * @return int      The query limit or null if none is set
     */
    public function getLimit()
    {
        return $this->limitCount;
    }

    /**
     * Get the query starting offset
     *
     * @return int      The query offset or null if none is set
     */
    public function getOffset()
    {
        return $this->limitOffset;
    }

    /**
     * Implementation specific initialization
     *
     * Overwrite this instead of __construct (it's called at the end of the construct) to
     * implement custom initialization logic on construction time
     */
    protected function init()
    {
    }

    /**
     * Return all columns set in this query or the default columns if none are set
     *
     * @return array    An array of columns
     */
    public function getColumns()
    {
        return ($this->columns !== null) ? $this->columns : $this->getDefaultColumns();
    }


    /**
     * Return all columns used for ordering
     *
     * @return array
     */
    public function getOrderColumns()
    {
        return $this->orderColumns;
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function clearFilter()
    {
        $this->filter = new Tree();
    }

    /**
     * Return a pagination adapter for this query
     *
     * @return \Zend_Paginator
     */
    public function paginate($limit = null, $page = null)
    {
        if ($page === null || $limit === null) {
            $request = \Zend_Controller_Front::getInstance()->getRequest();

            if ($page === null) {
                $page = $request->getParam('page', 0);
            }

            if ($limit === null) {
                $limit = $request->getParam('limit', 20);
            }
        }
        $this->limit($limit, $page * $limit);

        $paginator = new Zend_Paginator(new QueryAdapter($this));

        $paginator->setItemCountPerPage($limit);
        $paginator->setCurrentPageNumber($page);

        return $paginator;
    }


    /**
     * Parse a backend specific filter expression and return a Query\Node object
     *
     * @param $expression       The expression to parse
     * @param $parameters       Optional parameters for the expression
     *
     * @return Node             A query node or null if it's an invalid expression
     */
    protected function parseFilterExpression($expression, $parameter = null)
    {
        $splitted = explode(' ', $expression, 3);
        if (count($splitted) === 1 && $parameter) {
            return Node::createOperatorNode(Node::OPERATOR_EQUALS, $splitted[0], $parameter);
        } elseif (count($splitted) === 2 && $parameter) {
            Node::createOperatorNode($splitted[0], $splitted[1], is_string($parameter));
            return Node::createOperatorNode(Node::OPERATOR_EQUALS, $splitted[0], $parameter);
        } elseif (count($splitted) === 3) {
            if (trim($splitted[2]) === '?' && is_string($parameter)) {
                return Node::createOperatorNode($splitted[1], $splitted[0], $parameter);
            } else {
                return Node::createOperatorNode($splitted[1], $splitted[0], $splitted[2]);
            }
        }
        return null;
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
}
