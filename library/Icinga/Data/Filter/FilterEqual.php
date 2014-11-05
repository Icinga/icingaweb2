<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data\Filter;

class FilterEqual extends FilterExpression
{
    public function matches($row)
    {
        return (string) $row->{$this->column} === (string) $this->expression;
    }
}
