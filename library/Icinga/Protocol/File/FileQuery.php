<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\File;

use Icinga\Data\SimpleQuery;
use Icinga\Data\Filter\Filter;

/**
 * Class FileQuery
 *
 * Query for Datasource Icinga\Protocol\File\FileReader
 *
 * @package Icinga\Protocol\File
 */
class FileQuery extends SimpleQuery
{
    /**
     * Sort direction
     *
     * @var int
     */
    private $sortDir;

    /**
     * Filters to apply on result
     *
     * @var array
     */
    private $filters = array();

    /**
     * Nothing to do here
     */
    public function applyFilter(Filter $filter)
    {
    }

    /**
     * Sort query result chronological
     *
     * @param string $dir  Sort direction, 'ASC' or 'DESC' (default)
     *
     * @return FileQuery
     */
    public function order($field, $direction = null)
    {
        $this->sortDir = (
            $direction === null || strtoupper(trim($direction)) === 'DESC'
        ) ? self::SORT_DESC : self::SORT_ASC;
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
     * @return FileQuery
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
