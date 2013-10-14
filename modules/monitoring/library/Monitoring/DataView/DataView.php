<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\DataView;

use Icinga\Data\AbstractQuery;
use Icinga\Filter\Filterable;
use Icinga\Filter\Query\Tree;
use Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\Filter\UrlViewFilter;
use Icinga\Web\Request;

/**
 * A read-only view of an underlying Query
 */
abstract class DataView implements Filterable
{
    /**
     * Sort in ascending order, default
     */
    const SORT_ASC = AbstractQuery::SORT_ASC;
    /**
     * Sort in reverse order
     */
    const SORT_DESC = AbstractQuery::SORT_DESC;
    /**
     * The query used to populate the view
     *
     * @var AbstractQuery
     */
    private $query;

    /**
     * Create a new view
     *
     * @param Backend $ds         Which backend to query
     * @param array $columns    Select columns
     */
    public function __construct(Backend $ds, array $columns = null)
    {
        $filter = new UrlViewFilter($this);
        $this->query = $ds->select()->from(static::getTableName(), $columns === null ? $this->getColumns() : $columns);
        $this->applyFilter($filter->parseUrl());
    }

    /**
     * Get the queried table name
     *
     * @return string
     */
    public static function getTableName()
    {
        $tableName = explode('\\', get_called_class());
        $tableName = strtolower(end($tableName));
        return $tableName;
    }

    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    abstract public function getColumns();

    public function applyFilter(Tree $filter)
    {
        return $this->query->applyFilter($filter);
    }

    /**
     * Create view from request
     *
     * @param   Request $request
     * @param   array $columns
     *
     * @return  static
     */
    public static function fromRequest($request, array $columns = null)
    {
        $view = new static(Backend::createBackend($request->getParam('backend')), $columns);
        $view->filter($request->getParams());
        $order = $request->getParam('dir');
        if ($order !== null) {
            if (strtolower($order) === 'desc') {
                $order = self::SORT_DESC;
            } else {
                $order = self::SORT_ASC;
            }
        }
        $view->sort(
            $request->getParam('sort'),
            $order
        );
        return $view;
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
        $view->filter($params);
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
     * Filter rows that match all of the given filters. If a filter is not valid, it's silently ignored
     *
     * @param   array $filters
     *
     * @see     Filterable::isValidFilterTarget()
     */
    public function filter(array $filters)
    {
        foreach ($filters as $column => $filter) {
            if ($this->isValidFilterTarget($column)) {
                $this->query->where($column, $filter);
            }
        }
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

    /**
     * Sort the rows, according to the specified sort column and order
     *
     * @param   string $column   Sort column
     * @param   int $order    Sort order, one of the SORT_ constants
     *
     * @see     DataView::SORT_ASC
     * @see     DataView::SORT_DESC
     */
    public function sort($column = null, $order = null)
    {
        $sortRules = $this->getSortRules();
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
        foreach ($sortColumns['columns'] as $column) {
            $this->query->order($column, $order);
        }
    }

    /**
     * Retrieve default sorting rules for particular columns. These involve sort order and potential additional to sort
     *
     * @return array
     */
    abstract public function getSortRules();

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

    public function getFilterDomain()
    {
        return null;
    }
}
