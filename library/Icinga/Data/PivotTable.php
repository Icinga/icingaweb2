<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

use Icinga\Data\Filter\Filter;
use Icinga\Application\Icinga;
use Icinga\Web\Paginator\Adapter\QueryAdapter;
use Zend_Paginator;

class PivotTable
{
    /**
     * The query to fetch as pivot table
     *
     * @var SimpleQuery
     */
    protected $baseQuery;

    /**
     * The query to fetch the x axis labels
     *
     * @var SimpleQuery
     */
    protected $xAxisQuery;

    /**
     * The query to fetch the y axis labels
     *
     * @var SimpleQuery
     */
    protected $yAxisQuery;

    /**
     * The column that contains the labels for the x axis
     *
     * @var string
     */
    protected $xAxisColumn;

    /**
     * The column that contains the labels for the y axis
     *
     * @var string
     */
    protected $yAxisColumn;

    /**
     * The filter being applied on the query for the x axis
     *
     * @var Filter
     */
    protected $xAxisFilter;

    /**
     * The filter being applied on the query for the y axis
     *
     * @var Filter
     */
    protected $yAxisFilter;

    /**
     * Create a new pivot table
     *
     * @param   SimpleQuery $query          The query to fetch as pivot table
     * @param   string      $xAxisColumn    The column that contains the labels for the x axis
     * @param   string      $yAxisColumn    The column that contains the labels for the y axis
     */
    public function __construct(SimpleQuery $query, $xAxisColumn, $yAxisColumn)
    {
        $this->baseQuery = $query;
        $this->xAxisColumn = $xAxisColumn;
        $this->yAxisColumn = $yAxisColumn;
    }

    /**
     * Set the filter to apply on the query for the x axis
     *
     * @param   Filter  $filter
     *
     * @return  $this
     */
    public function setXAxisFilter(Filter $filter = null)
    {
        $this->xAxisFilter = $filter;
        return $this;
    }

    /**
     * Set the filter to apply on the query for the y axis
     *
     * @param   Filter  $filter
     *
     * @return  $this
     */
    public function setYAxisFilter(Filter $filter = null)
    {
        $this->yAxisFilter = $filter;
        return $this;
    }

    /**
     * Return the value for the given request parameter
     *
     * @param   string  $axis       The axis for which to return the parameter ('x' or 'y')
     * @param   string  $param      The parameter name to return
     * @param   int     $default    The default value to return
     *
     * @return  int
     */
    protected function getPaginationParameter($axis, $param, $default = null)
    {
        $request = Icinga::app()->getRequest();

        $value = $request->getParam($param, '');
        if (strpos($value, ',') > 0) {
            $parts = explode(',', $value, 2);
            return intval($parts[$axis === 'x' ? 0 : 1]);
        }

        return $default !== null ? $default : 0;
    }

    /**
     * Query horizontal (x) axis
     *
     * @return SimpleQuery
     */
    protected function queryXAxis()
    {
        if ($this->xAxisQuery === null) {
            $this->xAxisQuery = clone $this->baseQuery;
            $this->xAxisQuery->group($this->xAxisColumn);
            $this->xAxisQuery->columns(array($this->xAxisColumn));
            $this->xAxisQuery->setUseSubqueryCount();

            if ($this->xAxisFilter !== null) {
                $this->xAxisQuery->addFilter($this->xAxisFilter);
            }

            if (! $this->xAxisQuery->hasOrder($this->xAxisColumn)) {
                $this->xAxisQuery->order($this->xAxisColumn, 'asc');
            }
        }

        return $this->xAxisQuery;
    }

    /**
     * Query vertical (y) axis
     *
     * @return SimpleQuery
     */
    protected function queryYAxis()
    {
        if ($this->yAxisQuery === null) {
            $this->yAxisQuery = clone $this->baseQuery;
            $this->yAxisQuery->group($this->yAxisColumn);
            $this->yAxisQuery->columns(array($this->yAxisColumn));
            $this->yAxisQuery->setUseSubqueryCount();

            if ($this->yAxisFilter !== null) {
                $this->yAxisQuery->addFilter($this->yAxisFilter);
            }

            if (! $this->yAxisQuery->hasOrder($this->yAxisColumn)) {
                $this->yAxisQuery->order($this->yAxisColumn, 'asc');
            }
        }

        return $this->yAxisQuery;
    }

    /**
     * Return a pagination adapter for the x axis query
     *
     * $limit and $page are taken from the current request if not given.
     *
     * @param   int     $limit  The maximum amount of entries to fetch
     * @param   int     $page   The page to set as current one
     *
     * @return  Zend_Paginator
     */
    public function paginateXAxis($limit = null, $page = null)
    {
        if ($limit === null || $page === null) {
            if ($limit === null) {
                $limit = $this->getPaginationParameter('x', 'limit', 20);
            }

            if ($page === null) {
                $page = $this->getPaginationParameter('x', 'page', 1);
            }
        }

        $query = $this->queryXAxis();
        $query->limit($limit, $page > 0 ? ($page - 1) * $limit : 0);

        $paginator = new Zend_Paginator(new QueryAdapter($query));
        $paginator->setItemCountPerPage($limit);
        $paginator->setCurrentPageNumber($page);
        return $paginator;
    }

    /**
     * Return a pagination adapter for the y axis query
     *
     * $limit and $page are taken from the current request if not given.
     *
     * @param   int     $limit  The maximum amount of entries to fetch
     * @param   int     $page   The page to set as current one
     *
     * @return  Zend_Paginator
     */
    public function paginateYAxis($limit = null, $page = null)
    {
        if ($limit === null || $page === null) {
            if ($limit === null) {
                $limit = $this->getPaginationParameter('y', 'limit', 20);
            }

            if ($page === null) {
                $page = $this->getPaginationParameter('y', 'page', 1);
            }
        }

        $query = $this->queryYAxis();
        $query->limit($limit, $page > 0 ? ($page - 1) * $limit : 0);

        $paginator = new Zend_Paginator(new QueryAdapter($query));
        $paginator->setItemCountPerPage($limit);
        $paginator->setCurrentPageNumber($page);
        return $paginator;
    }

    /**
     * Return the pivot table as array
     *
     * @return array
     */
    public function toArray()
    {
        if (
            ($this->xAxisFilter === null && $this->yAxisFilter === null)
            || ($this->xAxisFilter !== null && $this->yAxisFilter !== null)
        ) {
            $xAxis = $this->queryXAxis()->fetchColumn();
            $yAxis = $this->queryYAxis()->fetchColumn();
        } else {
            if ($this->xAxisFilter !== null) {
                $xAxis = $this->queryXAxis()->fetchColumn();
                $yAxis = $this->queryYAxis()->where($this->xAxisColumn, $xAxis)->fetchColumn();
            } else { // $this->yAxisFilter !== null
                $yAxis = $this->queryYAxis()->fetchColumn();
                $xAxis = $this->queryXAxis()->where($this->yAxisColumn, $yAxis)->fetchColumn();
            }
        }

        $pivot = array();
        if (!empty($xAxis) && !empty($yAxis)) {
            $this->baseQuery->where($this->xAxisColumn, $xAxis)->where($this->yAxisColumn, $yAxis);

            foreach ($yAxis as $yLabel) {
                foreach ($xAxis as $xLabel) {
                    $pivot[$yLabel][$xLabel] = null;
                }
            }

            foreach ($this->baseQuery as $row) {
                $pivot[$row->{$this->yAxisColumn}][$row->{$this->xAxisColumn}] = $row;
            }
        }

        return $pivot;
    }
}
