<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

/**
 * Interface for specifying data sources
 */
interface Queryable
{
    /**
     * Set the target and fields to query
     *
     * @param   string  $target
     * @param   array   $fields
     *
     * @return  Fetchable
     */
    public function from($target, array $fields = null);
}
