<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Data\Filter;

class FilterEqual extends FilterExpression
{
    public function matches($row)
    {
        return (string) $row->{$this->column} === (string) $this->expression;
    }
}
