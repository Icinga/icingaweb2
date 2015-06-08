<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Filter;

class FilterExpression extends Filter
{
    protected $column;
    protected $sign;
    protected $expression;

    public function __construct($column, $sign, $expression)
    {
        $column = trim($column);
        $this->column = $column;
        $this->sign = $sign;
        $this->expression = $expression;
    }

    public function isExpression()
    {
        return true;
    }

    public function isChain()
    {
        return false;
    }

    public function isEmpty()
    {
        return false;
    }

    public function getColumn()
    {
        return $this->column;
    }

    public function getSign()
    {
        return $this->sign;
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

    public function setExpression($expression)
    {
        $this->expression = $expression;
        return $this;
    }

    public function setSign($sign)
    {
        if ($sign !== $this->sign) {
            return Filter::expression($this->column, $sign, $this->expression);
        }
        return $this;
    }

    public function listFilteredColumns()
    {
        return array($this->getColumn());
    }

    public function __toString()
    {
        $expression = is_array($this->expression) ?
             '( ' . implode(' | ', $this->expression) . ' )' :
             $this->expression;

        return sprintf(
            '%s %s %s',
            $this->column,
            $this->sign,
            $expression
        );
    }

    public function toQueryString()
    {
        $expression = is_array($this->expression) ?
             '(' . implode('|', $this->expression) . ')' :
             $this->expression;

        return $this->column . $this->sign . $expression;
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
    }

    public function andFilter(Filter $filter)
    {
        return Filter::matchAll($this, $filter);
    }

    public function orFilter(Filter $filter)
    {
        return Filter::matchAny($this, $filter);
    }
}
