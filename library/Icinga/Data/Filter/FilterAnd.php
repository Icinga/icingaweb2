<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data\Filter;

/**
 * Filter list AND 
 *
 * Binary AND, all contained filters must succeed
 */
class FilterAnd extends FilterChain
{
    protected $operatorName = 'AND';

    protected $operatorSymbol = '&';

    /**
     * Whether the given row object matches this filter
     *
     * @object $row
     * @return boolean
     */
    public function matches($row)
    {
        foreach ($this->filters as $filter) {
            if (! $filter->matches($row)) {
                return false;
            }
        }
        return true;
    }
}
