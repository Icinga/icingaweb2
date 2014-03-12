<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\DataView;

use Icinga\Data\BaseQuery;
use Icinga\Data\PivotTable;
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
