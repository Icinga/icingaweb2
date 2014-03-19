<?php

namespace Icinga\Protocol\File;

use Icinga\Data\BaseQuery;

class Query extends BaseQuery
{
    private $sortDir;

    private $filters = array();

    public function applyFilter()
    {}// ?

    public function order($dir)
    {
        $this->sortDir = ($dir === null || strtoupper(trim($dir)) === 'DESC') ? self::SORT_DESC : self::SORT_ASC;
        return $this;
    }

    public function sortDesc()
    {
        return $this->sortDir === self::SORT_DESC;
    }

    public function where($expression)
    {
        $this->filters[] = $expression;
        return $this;
    }

    public function getFilters()
    {
        return $this->filters;
    }
}