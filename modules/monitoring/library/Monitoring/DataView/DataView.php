<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\DataView;

use Icinga\Data\BaseQuery;
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
    const SORT_ASC = BaseQuery::SORT_ASC;
    /**
     * Sort in reverse order
     */
    const SORT_DESC = BaseQuery::SORT_DESC;
    /**
     * The query used to populate the view
     *
     * @var BaseQuery
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
        $this->query = $ds->select()->from(static::getTableName(), $columns === null ? $this->getColumns() : $columns);
    }

    /**
     * Get the queried table name
     *
     * @return string
     */
    public static function getTableName()
    {
        $tableName = explode('\\', get_called_class());
        $tableName = end($tableName);
        return $tableName;
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
     */
    public static function fromRequest($request, array $columns = null)
    {

        $view = new static(Backend::createBackend($request->getParam('backend')), $columns);
        $parser = new UrlViewFilter($view);
        $view->getQuery()->setFilter($parser->fromRequest($request));

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

        foreach ($params as $key => $value) {
            if ($view->isValidFilterTarget($key)) {
                $view->getQuery()->where($key, $value);
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
            $order = ($order === self::SORT_ASC) ? 'ASC' : 'DESC';

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

    public function applyFilter()
    {
        $this->query->applyFilter();
        return $this;
    }

    public function clearFilter()
    {
        $this->query->clearFilter();
        return $this;
    }

    public function addFilter($filter)
    {
        $this->query->addFilter($filter);
        return $this;
    }
}
