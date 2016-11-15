<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

use IteratorAggregate;
use Icinga\Application\Hook;
use Icinga\Data\ConnectionInterface;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterMatch;
use Icinga\Data\FilterColumns;
use Icinga\Data\PivotTable;
use Icinga\Data\QueryInterface;
use Icinga\Data\SortRules;
use Icinga\Exception\QueryException;
use Icinga\Module\Monitoring\Backend\Ido\Query\IdoQuery;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Web\Request;
use Icinga\Web\Url;

/**
 * A read-only view of an underlying query
 */
abstract class DataView implements QueryInterface, SortRules, FilterColumns, IteratorAggregate
{
    /**
     * The query used to populate the view
     *
     * @var IdoQuery
     */
    protected $query;

    protected $connection;

    protected $isSorted = false;

    /**
     * The cache for all filter columns
     *
     * @var array
     */
    protected $filterColumns;

    /**
     * Create a new view
     *
     * @param ConnectionInterface   $connection
     * @param array                 $columns
     */
    public function __construct(ConnectionInterface $connection, array $columns = null)
    {
        $this->connection = $connection;
        $this->query = $connection->query($this->getQueryName(), $columns);
    }

    /**
     * Return a iterator for all rows of the result set
     *
     * @return  IdoQuery
     */
    public function getIterator()
    {
        return $this->getQuery();
    }

    /**
     * Return the current position of the result set's iterator
     *
     * @return  int
     */
    public function getIteratorPosition()
    {
        return $this->query->getIteratorPosition();
    }

    /**
     * Get the query name this data view relies on
     *
     * By default this is this class' name without its namespace
     *
     * @return string
     */
    public static function getQueryName()
    {
        $tableName = explode('\\', get_called_class());
        $tableName = end($tableName);
        return $tableName;
    }

    public function where($condition, $value = null)
    {
        $this->query->where($condition, $value);
        return $this;
    }

    public function dump()
    {
        if (! $this->isSorted) {
            $this->order();
        }
        return $this->query->dump();
    }

    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    abstract public function getColumns();

    /**
     * Create view from request
     *
     * @param   Request $request
     * @param   array $columns
     *
     * @return  static
     * @deprecated Use $backend->select()->from($viewName) instead
     */
    public static function fromRequest($request, array $columns = null)
    {
        $view = new static(MonitoringBackend::instance($request->getParam('backend')), $columns);
        $view->applyUrlFilter($request);

        return $view;
    }

    protected function getHookedColumns()
    {
        $columns = array();
        foreach (Hook::all('monitoring/dataviewExtension') as $hook) {
            foreach ($hook->getAdditionalQueryColumns($this->getQueryName()) as $col) {
                $columns[] = $col;
            }
        }

        return $columns;
    }

    // TODO: This is not the right place for this, move it away
    protected function applyUrlFilter($request = null)
    {
        $url = Url::fromRequest();

        $limit = $url->shift('limit');
        $sort = $url->shift('sort');
        $dir = $url->shift('dir');
        $page = $url->shift('page');
        $format = $url->shift('format');
        $view = $url->shift('view');
        $view = $url->shift('backend');
        foreach ($url->getParams() as $k => $v) {
            $this->where($k, $v);
        }
        if ($sort) {
            $this->order($sort, $dir);
        }
    }

    /**
     * Create view from params
     *
     * @param   array $params
     * @param   array $columns
     *
     * @return  static
     */
    public static function fromParams(array $params, array $columns = null)
    {
        $view = new static(MonitoringBackend::instance($params['backend']), $columns);

        foreach ($params as $key => $value) {
            if ($view->isValidFilterTarget($key)) {
                $view->where($key, $value);
            }
        }

        if (isset($params['sort'])) {

            $order = isset($params['order']) ? $params['order'] : null;
            if ($order !== null) {
                if (strtolower($order) === 'desc') {
                    $order = self::SORT_DESC;
                } else {
                    $order = self::SORT_ASC;
                }
            }

            $view->sort($params['sort'], $order);
        }
        return $view;
    }

    /**
     * Check whether the given column is a valid filter column
     *
     * @param   string  $column
     *
     * @return  bool
     */
    public function isValidFilterTarget($column)
    {
        // Customvar
        if ($column[0] === '_' && preg_match('/^_(?:host|service)_/i', $column)) {
            return true;
        }
        return in_array($column, $this->getColumns()) || in_array($column, $this->getStaticFilterColumns());
    }

    /**
     * Return all filter columns with their optional label as key
     *
     * This will merge the results of self::getColumns(), self::getStaticFilterColumns() and
     * self::getDynamicFilterColumns() *once*. (i.e. subsequent calls of this function will
     * return the same result.)
     *
     * @return  array
     */
    public function getFilterColumns()
    {
        if ($this->filterColumns === null) {
            $columns = array_merge(
                $this->getColumns(),
                $this->getStaticFilterColumns(),
                $this->getDynamicFilterColumns()
            );

            $this->filterColumns = array();
            foreach ($columns as $label => $column) {
                if (is_int($label)) {
                    $label = ucwords(str_replace('_', ' ', $column));
                }

                if ($this->query->isCaseInsensitive($column)) {
                    $label .= ' ' . t('(Case insensitive)');
                }

                $this->filterColumns[$label] = $column;
            }
        }

        return $this->filterColumns;
    }

    /**
     * Return all static filter columns
     *
     * @return  array
     */
    public function getStaticFilterColumns()
    {
        return array();
    }

    /**
     * Return all dynamic filter columns such as custom variables
     *
     * @return  array
     */
    public function getDynamicFilterColumns()
    {
        $columns = array();
        if (! $this->query->allowsCustomVars()) {
            return $columns;
        }

        $query = MonitoringBackend::instance()
            ->select()
            ->from('customvar', array('varname', 'object_type'))
            ->where('is_json', 0)
            ->where('object_type_id', array(1, 2))
            ->getQuery()->group(array('varname', 'object_type'));
        foreach ($query as $row) {
            if ($row->object_type === 'host') {
                $label = t('Host') . ' ' . ucwords(str_replace('_', ' ', $row->varname));
                $columns[$label] = '_host_' . $row->varname;
            } else { // $row->object_type === 'service'
                $label = t('Service') . ' ' . ucwords(str_replace('_', ' ', $row->varname));
                $columns[$label] = '_service_' . $row->varname;
            }
        }

        return $columns;
    }

    /**
     * Return the current filter
     *
     * @return  Filter
     */
    public function getFilter()
    {
        return $this->query->getFilter();
    }

    /**
     * Return a pivot table for the given columns based on the current query
     *
     * @param   string  $xAxisColumn    The column to use for the x axis
     * @param   string  $yAxisColumn    The column to use for the y axis
     * @param   Filter  $xAxisFilter    The filter to apply on a query for the x axis
     * @param   Filter  $yAxisFilter    The filter to apply on a query for the y axis
     *
     * @return  PivotTable
     */
    public function pivot($xAxisColumn, $yAxisColumn, Filter $xAxisFilter = null, Filter $yAxisFilter = null)
    {
        $pivot = new PivotTable($this->query, $xAxisColumn, $yAxisColumn);
        return $pivot->setXAxisFilter($xAxisFilter)->setYAxisFilter($yAxisFilter);
    }

    /**
     * Sort the rows, according to the specified sort column and order
     *
     * @param   string  $column Sort column
     * @param   string  $order  Sort order, one of the SORT_ constants
     *
     * @return  $this
     * @throws  QueryException  If the sort column is not allowed
     * @see     DataView::SORT_ASC
     * @see     DataView::SORT_DESC
     * @deprecated Use DataView::order() instead
     */
    public function sort($column = null, $order = null)
    {
        $sortRules = $this->getSortRules();
        if ($column === null) {
            // Use first available sort rule as default
            if (empty($sortRules)) {
                return $this;
            }
            $sortColumns = reset($sortRules);
            if (! isset($sortColumns['columns'])) {
                $sortColumns['columns'] = array(key($sortRules));
            }
        } else {
            if (isset($sortRules[$column])) {
                $sortColumns = $sortRules[$column];
                if (! isset($sortColumns['columns'])) {
                    $sortColumns['columns'] = array($column);
                }
            } else {
                $sortColumns = array(
                    'columns' => array($column),
                    'order' => $order
                );
            };
        }

        $order = $order === null ? (isset($sortColumns['order']) ? $sortColumns['order'] : static::SORT_ASC) : $order;
        $order = (strtoupper($order) === static::SORT_ASC) ? 'ASC' : 'DESC';

        foreach ($sortColumns['columns'] as $column) {
            list($column, $direction) = $this->query->splitOrder($column);
            if (! $this->isValidFilterTarget($column)) {
                throw new QueryException(
                    mt('monitoring', 'The sort column "%s" is not allowed in "%s".'),
                    $column,
                    get_class($this)
                );
            }
            $this->query->order($column, $direction !== null ? $direction : $order);
        }
        $this->isSorted = true;
        return $this;
    }

    /**
     * Retrieve default sorting rules for particular columns. These involve sort order and potential additional to sort
     *
     * @return array
     */
    public function getSortRules()
    {
        return array();
    }

    /**
     * Sort result set either by the given column (and direction) or the sort defaults
     *
     * @param  string   $column
     * @param  string   $direction
     *
     * @return $this
     */
    public function order($column = null, $direction = null)
    {
        return $this->sort($column, $direction);
    }

    /**
     * Whether an order is set
     *
     * @return bool
     */
    public function hasOrder()
    {
        return $this->query->hasOrder();
    }

    /**
     * Get the order if any
     *
     * @return array|null
     */
    public function getOrder()
    {
        return $this->query->getOrder();
    }

    public function getMappedField($field)
    {
        return $this->query->getMappedField($field);
    }

    /**
     * Return the query which was created in the constructor
     *
     * @return \Icinga\Data\SimpleQuery
     */
    public function getQuery()
    {
        if (! $this->isSorted) {
            $this->order();
        }
        return $this->query;
    }

    public function applyFilter(Filter $filter)
    {
        $this->validateFilterColumns($filter);

        return $this->addFilter($filter);
    }

    /**
     * Validates recursive the Filter columns against the isValidFilterTarget() method
     *
     * @param Filter $filter
     *
     * @throws \Icinga\Data\Filter\FilterException
     */
    public function validateFilterColumns(Filter $filter)
    {
        if ($filter instanceof FilterMatch) {
            if (! $this->isValidFilterTarget($filter->getColumn())) {
                throw new QueryException(
                    mt('monitoring', 'The filter column "%s" is not allowed here.'),
                    $filter->getColumn()
                );
            }
        }

        if (method_exists($filter, 'filters')) {
            foreach ($filter->filters() as $filter) {
                $this->validateFilterColumns($filter);
            }
        }
    }

    public function clearFilter()
    {
        $this->query->clearFilter();
        return $this;
    }

    /**
     * @deprecated(EL): Only use DataView::applyFilter() for applying filter because all other functions are missing
     * column validation. Filter::matchAny() for the IdoQuery (or the DbQuery or the SimpleQuery I didn't have a look)
     * is required for the filter to work properly.
     */
    public function setFilter(Filter $filter)
    {
        $this->query->setFilter($filter);
        return $this;
    }

    /**
     * Get the view's search columns
     *
     * @return string[]
     */
    public function getSearchColumns()
    {
        return array();
    }

    /**
     * @deprecated(EL): Only use DataView::applyFilter() for applying filter because all other functions are missing
     * column validation.
     */
    public function addFilter(Filter $filter)
    {
        $this->query->addFilter($filter);
        return $this;
    }

    /**
     * Count result set
     *
     * @return int
     */
    public function count()
    {
        return $this->query->count();
    }

    /**
     * Set whether the query should peek ahead for more results
     *
     * Enabling this causes the current query limit to be increased by one. The potential extra row being yielded will
     * be removed from the result set. Note that this only applies when fetching multiple results of limited queries.
     *
     * @return  $this
     */
    public function peekAhead($state = true)
    {
        $this->query->peekAhead($state);
        return $this;
    }

    /**
     * Return whether the query did not yield all available results
     *
     * @return  bool
     */
    public function hasMore()
    {
        return $this->query->hasMore();
    }

    /**
     * Return whether this query will or has yielded any result
     *
     * @return  bool
     */
    public function hasResult()
    {
        return $this->query->hasResult();
    }

    /**
     * Set a limit count and offset
     *
     * @param   int $count  Number of rows to return
     * @param   int $offset Start returning after this many rows
     *
     * @return  self
     */
    public function limit($count = null, $offset = null)
    {
        $this->query->limit($count, $offset);
        return $this;
    }

    /**
     * Whether a limit is set
     *
     * @return bool
     */
    public function hasLimit()
    {
        return $this->query->hasLimit();
    }

    /**
     * Get the limit if any
     *
     * @return int|null
     */
    public function getLimit()
    {
        return $this->query->getLimit();
    }

    /**
     * Whether an offset is set
     *
     * @return bool
     */
    public function hasOffset()
    {
        return $this->query->hasOffset();
    }

    /**
     * Get the offset if any
     *
     * @return int|null
     */
    public function getOffset()
    {
        return $this->query->getOffset();
    }

    /**
     * Retrieve an array containing all rows of the result set
     *
     * @return  array
     */
    public function fetchAll()
    {
        return $this->getQuery()->fetchAll();
    }

    /**
     * Fetch the first row of the result set
     *
     * @return  mixed
     */
    public function fetchRow()
    {
        return $this->getQuery()->fetchRow();
    }

    /**
     * Fetch the first column of all rows of the result set as an array
     *
     * @return  array
     */
    public function fetchColumn()
    {
        return $this->getQuery()->fetchColumn();
    }

    /**
     * Fetch the first column of the first row of the result set
     *
     * @return  string
     */
    public function fetchOne()
    {
        return $this->getQuery()->fetchOne();
    }

    /**
     * Fetch all rows of the result set as an array of key-value pairs
     *
     * The first column is the key, the second column is the value.
     *
     * @return  array
     */
    public function fetchPairs()
    {
        return $this->getQuery()->fetchPairs();
    }
}
