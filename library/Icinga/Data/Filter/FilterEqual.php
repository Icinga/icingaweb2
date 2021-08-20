<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Filter;

class FilterEqual extends FilterExpression
{
    public function matches($row)
    {
        if (! isset($row->{$this->column})) {
            return false;
        }

        return (string) $row->{$this->column} === (string) $this->expression;
    }
}
