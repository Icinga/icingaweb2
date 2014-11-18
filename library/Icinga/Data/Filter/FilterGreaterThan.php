<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data\Filter;

class FilterGreaterThan extends FilterExpression
{
    public function matches($row)
    {
        if (! isset($row->{$this->column})) {
            // TODO: REALLY? Exception?
            return false;
        }
        return (string) $row->{$this->column} > (string) $this->expression;
    }
}
