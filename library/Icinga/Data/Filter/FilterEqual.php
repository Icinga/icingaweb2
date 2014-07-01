<?php

namespace Icinga\Data\Filter;

class FilterEqual extends FilterExpression
{
    public function matches($row)
    {
        return (string) $row->{$this->column} === (string) $this->expression;
    }
}
