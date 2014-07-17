<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data;

use Icinga\Data\Filter\Filter;

/**
 * Interface for filtering a result set
 */
interface Filterable
{
    public function applyFilter(Filter $filter);

    public function setFilter(Filter $filter);

    public function getFilter();

    public function addFilter(Filter $filter);

    public function where($condition, $value = null);
}
