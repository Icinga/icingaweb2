<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

use Icinga\Exception\StatementException;

/**
 * Interface for data insertion
 */
interface Extensible
{
    /**
     * Insert the given data for the given target
     *
     * @param   string  $target
     * @param   array   $data
     *
     * @throws  StatementException
     */
    public function insert($target, array $data);
}
