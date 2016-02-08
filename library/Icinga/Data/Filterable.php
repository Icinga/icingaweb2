<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

use Icinga\Data\Filter\Filter;

/**
 * Interface for filtering a result set
 *
 * @deprecated(EL): addFilter and applyFilter do the same in all usages.
 * addFilter could be replaced w/ getFilter()->add(). We must no require classes implementing this interface to
 * implement redundant methods over and over again. This interface must be moved to the namespace Icinga\Data\Filter.
 * It lacks documentation.
 */
interface Filterable
{
    public function applyFilter(Filter $filter);

    public function setFilter(Filter $filter);

    public function getFilter();

    public function addFilter(Filter $filter);

    public function where($condition, $value = null);
}
