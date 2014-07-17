<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data\Filter;

class FilterNot extends FilterChain
{
    protected $operatorName = 'NOT';

    protected $operatorSymbol = '!'; // BULLSHIT

// TODO: Max count 1 or autocreate sub-and?

    public function matches($row)
    {
        foreach ($this->filters() as $filter) {
            if ($filter->matches($row)) {
                return false;
            }
        }
        return true;
    }

    public function toQueryString()
    {
        $parts = array();
        if (empty($this->filters)) {
            return '';
        }

        foreach ($this->filters() as $filter) {
            $parts[] = $filter->toQueryString();
        }
        if (count($parts) === 1) {
            return '!' . $parts[0];
        } else {
            return '!(' . implode('&', $parts) . ')';
        }
    }

    public function __toString()
    {
        if (count($this->filters) === 1) {
            return '! ' . $this->filters[0];
        }
        return '! (' . implode('&', $this->filters) . ')';
    }
}
