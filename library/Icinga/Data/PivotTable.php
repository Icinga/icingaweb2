<?php

namespace Icinga\Data;

class PivotTable
{
    protected $query;

    protected $verticalColumn;

    protected $horizontalColumn;

    protected $limit;

    protected $offset;

    protected $verticalLimit;

    protected $horizontalLimit;

    public function __construct(QueryInterface $query, $verticalColumn, $horizontalColumn)
    {
        $this->query = $query;
        $this->verticalColumn   = $verticalColumn;
        $this->horizontalColumn = $horizontalColumn;
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
     * Fetch all columns
     */
    public function fetchAll()
    {
        $xcol = $this->horizontalColumn;
        $ycol = $this->verticalColumn;
        $queryX = clone($this->query);
        $queryX->columns($xcol);
        if ($this->limit !== null) {
            $queryX->limit($this->getLimit(), $this->getOffset());
        }
        $queryX->limit(40);
        $listX = $queryX->fetchColumn();
        $queryY = clone($this->query);

        $queryY->columns($ycol);
        if ($this->verticalLimit !== null) {
            $queryY->limit($this->getVerticalLimit(), $this->getVerticalOffset());
        }
        $queryY->limit(50);
        $listY = $queryY->fetchColumn();

        // TODO: resetOrder
        $this->query
            ->where($ycol, $listY)
            ->where($xcol, $listX)
            ->order($ycol)
            ->order($xcol);
        $pivot = array();
        $emptyrow = (object) array();
        foreach ($this->query->listColumns() as $col) {
            $emptyrow->$col = null;
        }
        foreach ($listY as $y) {
            foreach ($listX as $x) {
                $row = clone($emptyrow);
                $row->$xcol = $x;
                $row->$ycol = $y;
                $pivot[$y][$x] = $row;
            }
        }

        foreach ($this->query->fetchAll() as $row) {
            $pivot[$row->$ycol][$row->$xcol] = $row;
        }
        return $pivot;
    }
}
