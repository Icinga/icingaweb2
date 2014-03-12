<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data;

use Icinga\Data\BaseQuery;

class PivotTable
{
    /**
     * The query to fetch as pivot table
     *
     * @var BaseQuery
     */
    protected $query;

    /**
     * The column to use for the x axis
     *
     * @var string
     */
    protected $xAxisColumn;

    /**
     * The column to use for the y axis
     *
     * @var string
     */
    protected $yAxisColumn;

    protected $limit;

    protected $offset;

    protected $verticalLimit;

    protected $horizontalLimit;

    /**
     * Create a new pivot table
     *
     * @param   BaseQuery   $query          The query to fetch as pivot table
     * @param   string      $xAxisColumn    The column to use for the x axis
     * @param   string      $yAxisColumn    The column to use for the y axis
     */
    public function __construct(BaseQuery $query, $xAxisColumn, $yAxisColumn)
    {
        $this->query = $query;
        $this->xAxisColumn = $xAxisColumn;
        $this->yAxisColumn = $yAxisColumn;
    }

    public function limit($limit = null, $offset = null)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    public function getLimit()
    {
        if ($this->limit === null) {
            return 20;
        }
        return $this->limit;
    }

    public function getOffset()
    {
        if ($this->limit === null) {
            return 20;
        }
        return $this->offset;
    }

    public function verticalLimit($limit = null, $offset = null)
    {
        // TODO: Trigger limit by calling $this->limit()?
        if ($limit === null) {
            $limit = $this->getLimit();
        }
        if ($offset === null) {
            $offset = $this->getOffset();
        }
        $this->verticalLimit = $limit;
        $this->verticalOffset = $offset;
        return $this;
    }

    public function paginateVertical($limit = null, $offset = null)
    {
        $this->verticalLimit($limit, $offset);
        return Paginator($this);
    }

    public function getVerticalLimit()
    {
        if ($this->verticalLimit === null) {
            return 20;
        }
        return $this->verticalLimit;
    }

    public function getVerticalOffset()
    {
        if ($this->verticalLimit === null) {
            return 20;
        }
        return $this->verticalOffset;
    }

    /**
     * Fetch the values to label the x axis with
     */
    protected function fetchXAxis()
    {
        $query = clone $this->query;
        $query->setColumns(array($this->xAxisColumn));
        return $query->fetchColumn();
    }

    /**
     * Fetch the values to label the y axis with
     */
    protected function fetchYAxis()
    {
        $query = clone $this->query;
        $query->setColumns(array($this->yAxisColumn));
        return $query->fetchColumn();
    }

    /**
     * Return the pivot table as array
     *
     * @return  array
     */
    public function toArray()
    {
        $xAxis = $this->fetchXAxis();
        $yAxis = $this->fetchYAxis();

        $this->query->where($this->xAxisColumn, $xAxis)->where($this->yAxisColumn, $yAxis);

        $pivot = array();
        foreach ($this->query->fetchAll() as $row) {
            if (!array_key_exists($row->{$this->yAxisColumn}, $pivot)) {
                $defaults = array();
                foreach ($xAxis as $label) {
                    $defaults[$label] = null;
                }
                $pivot[$row->{$this->yAxisColumn}] = $defaults;
            }

            $pivot[$row->{$this->yAxisColumn}][$row->{$this->xAxisColumn}] = $row;
        }

        return $pivot;
    }
}
