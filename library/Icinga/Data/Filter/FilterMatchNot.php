<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Filter;

class FilterMatchNot extends FilterExpression
{
    public function matches($row)
    {
        return !parent::matches($row);
    }
}
