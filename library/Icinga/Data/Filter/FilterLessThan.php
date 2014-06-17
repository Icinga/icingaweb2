<?php

namespace Icinga\Data\Filter;

class FilterLessThan extends FilterExpression
{
    public function __toString()
    {
        return $this->column . ' < ' . $this->expression;
    }

    public function toQueryString()
    {
        return $this->column . '<' . $this->expression;
    }

    public function matches($row)
    {
        return (string) $row->{$this->column} < (string) $this->expression;
    }
}
