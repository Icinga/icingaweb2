<?php

namespace Icinga\Data\Filter;

class FilterGreaterThan extends FilterExpression
{

    public function matches($row)
    {
        return (string) $row->{$this->column} > (string) $this->expression;
    }
}
