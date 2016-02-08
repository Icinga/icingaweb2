<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Filter;

class FilterNotEqual extends FilterExpression
{
    public function matches($row)
    {
        return (string) $row->{$this->column} !== (string) $this->expression;
    }
}
