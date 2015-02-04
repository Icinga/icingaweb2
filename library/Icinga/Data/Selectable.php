<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

/**
 * Interface for classes providing a data source to fetch data from
 */
interface Selectable
{
    /**
     * Provide a data source to fetch data from
     *
     * @return Queryable
     */
    public function select();
}
