<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data;

use Icinga\Data\SimpleQuery;
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
     * Create a new pivot table
     *
     * @param   SimpleQuery   $query          The query to fetch as pivot table
     * @param   string      $xAxisColumn    The column that contains the labels for the x axis
     * @param   string      $yAxisColumn    The column that contains the labels for the y axis
     */
    public function __construct(SimpleQuery $query, $xAxisColumn, $yAxisColumn)
    {
        $this->baseQuery = $query;
        $this->xAxisColumn = $xAxisColumn;
        $this->yAxisColumn = $yAxisColumn;
        $this->prepareQueries()->adjustSorting();
    }

    /**
     * Prepare the queries used for the pre processing
     *
     * @return  self
     */
    protected function prepareQueries()
    {
        $this->xAxisQuery = clone $this->baseQuery;
        $this->xAxisQuery->distinct();
        $this->xAxisQuery->columns(array($this->xAxisColumn));
        $this->yAxisQuery = clone $this->baseQuery;
        $this->yAxisQuery->distinct();
        $this->yAxisQuery->columns(array($this->yAxisColumn));

        return $this;
    }

    /**
     * Set a default sorting for the x- and y-axis without losing any existing rules
     *
     * @return  self
     */
    protected function adjustSorting()
    {
        $currentOrderColumns = $this->baseQuery->getOrder();
        $xAxisOrderColumns = array(array($this->baseQuery->getMappedField($this->xAxisColumn), SimpleQuery::SORT_ASC));
        $yAxisOrderColumns = array(array($this->baseQuery->getMappedField($this->yAxisColumn), SimpleQuery::SORT_ASC));

        foreach ($currentOrderColumns as $orderInfo) {
            if ($orderInfo[0] === $xAxisOrderColumns[0][0]) {
                $xAxisOrderColumns[0] = $orderInfo;
            } elseif ($orderInfo[0] === $yAxisOrderColumns[0][0]) {
                $yAxisOrderColumns[0] = $orderInfo;
            } else {
                $xAxisOrderColumns[] = $orderInfo;
                $yAxisOrderColumns[] = $orderInfo;
            }
        }
//TODO: simplify this whole function. No need to care about mapping
//        foreach ($xAxisOrderColumns as 
//        $this->xAxisQuery->setOrder($xAxisOrderColumns);
//        $this->yAxisQuery->setOrder($yAxisOrderColumns);
        return $this;
    }

    /**
     * Return the value for the given request parameter
     *
     * @param   string  $axis       The axis for which to return the parameter ('x' or 'y')
     * @param   string  $param      The parameter name to return
     * @param   int     $default    The default value to return
     *
     * @return int
     */
    protected function getPaginationParameter($axis, $param, $default = null)
    {
        $request = Icinga::app()->getFrontController()->getRequest();

        $value = $request->getParam($param, '');
        if (strpos($value, ',') > 0) {
            $parts = explode(',', $value, 2);
            return intval($parts[$axis === 'x' ? 0 : 1]);
        }

        return $default !== null ? $default : 0;
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

        $this->xAxisQuery->limit($limit, $page > 0 ? ($page - 1) * $limit : 0);

        $paginator = new Zend_Paginator(new QueryAdapter($this->xAxisQuery));
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

        $this->yAxisQuery->limit($limit, $page > 0 ? ($page - 1) * $limit : 0);

        $paginator = new Zend_Paginator(new QueryAdapter($this->yAxisQuery));
        $paginator->setItemCountPerPage($limit);
        $paginator->setCurrentPageNumber($page);
        return $paginator;
    }

    /**
     * Return the pivot table as array
     *
     * @return  array
     */
    public function toArray()
    {
        $pivot = array();
        $xAxis = $this->xAxisQuery->fetchColumn();
        $yAxis = $this->yAxisQuery->fetchColumn();

        if (!empty($xAxis) && !empty($yAxis)) {
            $this->baseQuery->where($this->xAxisColumn, $xAxis)->where($this->yAxisColumn, $yAxis);

            foreach ($yAxis as $yLabel) {
                foreach ($xAxis as $xLabel) {
                    $pivot[$yLabel][$xLabel] = null;
                }
            }

            foreach ($this->baseQuery->fetchAll() as $row) {
                $pivot[$row->{$this->yAxisColumn}][$row->{$this->xAxisColumn}] = $row;
            }
        }

        return $pivot;
    }
}
