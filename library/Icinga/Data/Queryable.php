<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

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
