<?php

namespace Icinga\Data\Filter;

class FilterOr extends FilterOperator
{
    protected $operatorName = 'OR';

    protected $operatorSymbol = '|';

    public function matches($row)
    {
        foreach ($this->filters as $filter) {
            if ($filter->matches($row)) {
                return true;
            }
        }
        return false;
    }
}
