<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

use Countable;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterMatch;
use Icinga\Data\Browsable;
use Icinga\Data\PivotTable;
use Icinga\Data\Sortable;
use Icinga\Data\ConnectionInterface;
use Icinga\Data\Filterable;
use Icinga\Exception\QueryException;
use Icinga\Web\Request;
use Icinga\Web\Url;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;

/**
 * A read-only view of an underlying query
 */
abstract class DataView implements Browsable, Countable, Filterable, Sortable
{
    /**
     * The query used to populate the view
     *
     * @var \Icinga\Data\SimpleQuery
     */
    protected $query;

    protected $filter;

    protected $connection;

    protected $isSorted = false;

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
        $this->filter = Filter::matchAll();
        $this->init();
    }

    /**
     * Initializer for `distinct purposes
     *
     * Implemented for `distinct as workaround
     *
     * @TODO Subject to change, see #7344
     */
    public function init()
    {
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
        $this->filter->addFilter(Filter::where($condition, $value));
        $this->query->where($condition, $value);
        return $this;
    }

    public function dump()
    {
        $this->order();
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
     * Check whether the given column is a valid filter column, i.e. the view actually provides the column or it's
     * a non-queryable filter column
     *
     * @param   string $column
     *
     * @return  bool
     */
    public function isValidFilterTarget($column)
    {
        return in_array($column, $this->getColumns()) || in_array($column, $this->getFilterColumns());
    }

    public function getFilterColumns()
    {
        return array();
    }

    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Return a pivot table for the given columns based on the current query
     *
     * @param   string  $xAxisColumn    The column to use for the x axis
     * @param   string  $yAxisColumn    The column to use for the y axis
     *
     * @return  PivotTable
     */
    public function pivot($xAxisColumn, $yAxisColumn)
    {
        return new PivotTable($this->query, $xAxisColumn, $yAxisColumn);
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
     * @deprecated(EL): Only use DataView::applyFilter() for applying filter because all other functions are missing
     * column validation.
     */
    public function addFilter(Filter $filter)
    {
        $this->query->addFilter(clone($filter));
        $this->filter = $filter; // TODO: Hmmmm.... and?
        return $this;
    }

    /**
     * Paginate data
     *
     * @param   int $itemsPerPage   Number of items per page
     * @param   int $pageNumber     Current page number
     *
     * @return  Zend_Paginator
     */
    public function paginate($itemsPerPage = null, $pageNumber = null)
    {
        if (! $this->isSorted) {
            $this->order();
        }
        return $this->query->paginate($itemsPerPage, $pageNumber);
    }

    /**
     * Count result set
     *
     * @return int
     */
    public function count()
    {
        return count($this->query);
    }
}
