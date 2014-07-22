<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
