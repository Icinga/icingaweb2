<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

use Icinga\Data\Filter\Filter;
use Icinga\Exception\StatementException;

/**
 * Interface for data updating
 */
interface Updatable
{
    /**
     * Update the target with the given data and optionally limit the affected entries by using a filter
     *
     * @param   string  $target
     * @param   array   $data
     * @param   Filter  $filter
     *
     * @throws  StatementException
     */
    public function update($target, array $data, Filter $filter = null);
}
