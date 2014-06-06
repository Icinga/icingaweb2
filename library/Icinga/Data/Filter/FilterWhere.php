<?php

namespace Icinga\Data\Filter;

class FilterWhere extends Filter
{
    protected $column;
    protected $expression;

    public function __construct($column, $expression)
    {
        $this->column = $column;
        $this->expression = $expression;
    }

    public function getColumn()
    {
        return $this->column;
    }

    public function setColumn($column)
    {
        $this->column = $column;
        return $this;
    }

    public function getExpression()
    {
        return $this->expression;
    }

    public function __toString()
    {
        if (is_array($this->expression)) {
            return $this->column . ' = ( ' . implode(' | ', $this->expression) . ' )';
        } else {
            return $this->column . ' = ' . $this->expression;
        }
    }

    public function toQueryString()
    {
        if (is_array($this->expression)) {
            return $this->column . '=' . implode('|', $this->expression);
        } else {
            return $this->column . '=' . $this->expression;
        }
    }

    public function matches($row)
    {
        if (is_array($this->expression)) {
            return in_array($row->{$this->column}, $this->expression);
        } elseif (strpos($this->expression, '*') === false) {
            return (string) $row->{$this->column} === (string) $this->expression;
        } else {
            $parts = preg_split('~\*~', $this->expression);
            foreach ($parts as & $part) {
                $part = preg_quote($part);
            }
            $pattern = '/^' . implode('.*', $parts) . '$/';
            return (bool) preg_match($pattern, $row->{$this->column});
        }

        foreach ($this->filters as $filter) {
            if (! $filter->matches($row)) {
                return false;
            }
        }
        return true;
    }
}
