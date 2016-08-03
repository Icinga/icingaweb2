<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Filter;

use Exception;

class FilterExpression extends Filter
{
    protected $column;
    protected $sign;
    protected $expression;

    /**
     * Does this filter compare case sensitive?
     *
     * @var bool
     */
    protected $caseSensitive;

    public function __construct($column, $sign, $expression)
    {
        $column = trim($column);
        $this->column = $column;
        $this->sign = $sign;
        $this->expression = $expression;
        $this->caseSensitive = true;
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

    /**
     * Return whether this filter compares case sensitive
     *
     * @return bool
     */
    public function getCaseSensitive()
    {
        return $this->caseSensitive;
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

    /**
     * Set this filter's case sensitivity
     *
     * @param   bool    $caseSensitive
     *
     * @return $this
     */
    public function setCaseSensitive($caseSensitive = true)
    {
        $this->caseSensitive = $caseSensitive;
        return $this;
    }

    public function listFilteredColumns()
    {
        return array($this->getColumn());
    }

    public function __toString()
    {
        if ($this->isBooleanTrue()) {
            return $this->column;
        }

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
        if ($this->isBooleanTrue()) {
            return $this->column;
        }

        $expression = is_array($this->expression) ?
             '(' . implode('|', array_map('rawurlencode', $this->expression)) . ')' :
             rawurlencode($this->expression);

        return $this->column . $this->sign . $expression;
    }

    protected function isBooleanTrue()
    {
        return $this->sign === '=' && $this->expression === true;
    }

    /**
     * If $var is a scalar, do the same as strtolower() would do.
     * If $var is an array, map $this->strtolowerRecursive() to its elements.
     * Otherwise, return $var unchanged.
     *
     * @param   mixed   $var
     *
     * @return  mixed
     */
    protected function strtolowerRecursive($var)
    {
        if ($var === null || is_scalar($var)) {
            return strtolower($var);
        }
        if (is_array($var)) {
            return array_map(array($this, 'strtolowerRecursive'), $var);
        }
        return $var;
    }

    public function matches($row)
    {
        try {
            $rowValue = $row->{$this->column};
        } catch (Exception $e) {
            // TODO: REALLY? Exception?
            return false;
        }

        if ($this->caseSensitive) {
            $expression = $this->expression;
        } else {
            $rowValue = $this->strtolowerRecursive($rowValue);
            $expression = $this->strtolowerRecursive($this->expression);
        }

        if (is_array($expression)) {
            return in_array($rowValue, $expression);
        }

        $expression = (string) $expression;
        if (strpos($expression, '*') === false) {
            if (is_array($rowValue)) {
                return in_array($expression, $rowValue);
            }

            return (string) $rowValue === $expression;
        }

        $parts = array();
        foreach (preg_split('~\*~', $expression) as $part) {
            $parts[] = preg_quote($part);
        }
        $pattern = '/^' . implode('.*', $parts) . '$/';

        if (is_array($rowValue)) {
            foreach ($rowValue as $candidate) {
                if (preg_match($pattern, $candidate)) {
                    return true;
                }
            }

            return false;
        }

        return (bool) preg_match($pattern, $rowValue);
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
