<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Filter;

class FilterEqualOrLessThan extends FilterExpression
{
    public function __toString()
    {
        return $this->column . ' <= ' . $this->expression;
    }

    public function toQueryString()
    {
        return $this->column . '<=' . $this->expression;
    }

    public function matches($row)
    {
        return (string) $row->{$this->column} <= (string) $this->expression;
    }
}
