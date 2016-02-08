<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

use Icinga\Data\Filter\Filter;
use Icinga\Exception\StatementException;

/**
 * Interface for data deletion
 */
interface Reducible
{
    /**
     * Delete entries in the given target, optionally limiting the affected entries by using a filter
     *
     * @param   string  $target
     * @param   Filter  $filter
     *
     * @throws  StatementException
     */
    public function delete($target, Filter $filter = null);
}
