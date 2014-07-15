<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\DataView;

use Icinga\Data\Filter\Filter;
use Icinga\Data\SimpleQuery;
use Icinga\Data\Browsable;
use Icinga\Data\PivotTable;
use Icinga\Data\Sortable;
use Icinga\Data\ConnectionInterface;
use Icinga\Data\Filterable;
use Icinga\Web\Request;
use Icinga\Web\Url;
use Icinga\Module\Monitoring\Backend;

/**
 * A read-only view of an underlying query
 */
abstract class DataView implements Browsable, Filterable, Sortable
{
    /**
     * The query used to populate the view
     *
     * @var SimpleQuery
     */
    private $query;
    
    protected $filter;

    protected $connection;

    /**
     * Create a new view
     *
     * @param SimpleQuery $query      Which backend to query
     * @param array     $columns    Select columns
     */
    public function __construct(ConnectionInterface $connection, array $columns = null)
    {
        $this->connection = $connection;
        $queryClass = $connection->getQueryClass($this->getQueryName());
        $this->query = new $queryClass($this->connection->getResource(), $columns);
        $this->filter = Filter::matchAll();
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
        $view = new static(Backend::createBackend($request->getParam('backend')), $columns);
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
        $view = new static(Backend::createBackend($params['backend']), $columns);

        foreach ($params as $key => $value) {
            if ($view->isValidFilterTarget($key)) {
                $view->where($key, $value);
            }
        }

        $order = isset($params['order']) ? $params['order'] : null;
        if ($order !== null) {
            if (strtolower($order) === 'desc') {
                $order = self::SORT_DESC;
            } else {
                $order = self::SORT_ASC;
            }
        }

        $view->sort(
            isset($params['sort']) ? $params['sort'] : null,
            $order
        );

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
     * @param   int     $order  Sort order, one of the SORT_ constants
     *
     * @return  self
     * @see     DataView::SORT_ASC
     * @see     DataView::SORT_DESC
     * @deprecated Use DataView::order() instead
     */
    public function sort($column = null, $order = null)
    {
        $sortRules = $this->getSortRules();

        if ($sortRules !== null) {
            if ($column === null) {
                $sortColumns = reset($sortRules);
                if (!isset($sortColumns['columns'])) {
                    $sortColumns['columns'] = array(key($sortRules));
                }
            } else {
                if (isset($sortRules[$column])) {
                    $sortColumns = $sortRules[$column];
                    if (!isset($sortColumns['columns'])) {
                        $sortColumns['columns'] = array($column);
                    }
                } else {
                    $sortColumns = array(
                        'columns' => array($column),
                        'order' => $order
                    );
                };
            }

            $order = $order === null ? (isset($sortColumns['order']) ? $sortColumns['order'] : self::SORT_ASC) : $order;
            $order = (strtoupper($order) === self::SORT_ASC) ? 'ASC' : 'DESC';

            foreach ($sortColumns['columns'] as $column) {
                $this->query->order($column, $order);
            }
        }
        return $this;
    }

    /**
     * Retrieve default sorting rules for particular columns. These involve sort order and potential additional to sort
     *
     * @return array
     */
    public function getSortRules()
    {
        return null;
    }

    /**
     * Sort result set either by the given column (and direction) or the sort defaults
     *
     * @param  string   $column
     * @param  string   $direction
     *
     * @return self
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
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

    public function applyFilter(Filter $filter)
    {
        return $this->addFilter($filter);
    }

    public function clearFilter()
    {
        $this->query->clearFilter();
        return $this;
    }

    public function setFilter(Filter $filter)
    {
        $this->query->setFilter($filter);
        return $this;
    }

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
        return $this->query->paginate($itemsPerPage, $pageNumber);
    }
}
