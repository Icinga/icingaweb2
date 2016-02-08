<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

interface FilterColumns
{
    /**
     * Return a filterable's filter columns with their optional label as key
     *
     * @return  array
     */
    public function getFilterColumns();

    /**
     * Return a filterable's search columns
     *
     * @return  array
     */
    public function getSearchColumns();
}
