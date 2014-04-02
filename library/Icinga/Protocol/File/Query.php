<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\File;

use Icinga\Data\BaseQuery;

class Query extends BaseQuery
{
    private $sortDir;

    private $filters = array();

    /**
     * Nothing to do here
     */
    public function applyFilter()
    {}

    /**
     * Sort query result chronological
     *
     * @param string $dir  Sort direction, 'ASC' or 'DESC' (default)
     *
     * @return Query
     */
    public function order($dir)
    {
        $this->sortDir = ($dir === null || strtoupper(trim($dir)) === 'DESC') ? self::SORT_DESC : self::SORT_ASC;
        return $this;
    }

    /**
     * Return true if sorting descending, false otherwise
     *
     * @return bool
     */
    public function sortDesc()
    {
        return $this->sortDir === self::SORT_DESC;
    }

    /**
     * Add an mandatory filter expression to be applied on this query
     *
     * @param string $expression  the filter expression to be applied
     *
     * @return Query
     */
    public function andWhere($expression)
    {
        $this->filters[] = $expression;
        return $this;
    }

    /**
     * Get filters currently applied on this query
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }
}