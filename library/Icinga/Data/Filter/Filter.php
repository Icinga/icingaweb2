<?php

namespace Icinga\Data\Filter;

use Icinga\Web\UrlParams;
use Icinga\Exception\ProgrammingError;

/**
 * Filter
 *
 * Base class for filters (why?) and factory for the different FilterOperators
 */
abstract class Filter
{
    protected $id = '1';

    public function setId($id)
    {
        $this->id = (string) $id;
        return $this;
    }

    abstract function toQueryString();

    public function getUrlParams()
    {
        return UrlParams::fromQueryString($this->toQueryString());
    }

    public function getById($id)
    {
        if ((string) $id === $this->getId()) {
            return $this;
        }
        throw new ProgrammingError(sprintf(
            'Trying to get invalid filter index "%s" from "%s" ("%s")', $id, $this, $this->id
        ));
    }

    public function getId()
    {
        return $this->id;
    }

    public function hasId($id)
    {
        if ($id === $this->getId()) {
            return true;
        }
        return false;
    }

    /**
     * Where Filter factory
     *
     * @param string $col     Column to be filtered
     * @param string $filter  Filter expression
     *
     * @throws FilterException
     * @return FilterWhere
     */
    public static function where($col, $filter)
    {
        return new FilterExpression($col, '=', $filter);
    }

    public static function expression($col, $op, $expression)
    {
        switch ($op) {
            case '=': return new FilterEqual($col, $op, $expression);
            case '<': return new FilterLessThan($col, $op, $expression);
            case '>': return new FilterGreaterThan($col, $op, $expression);
            case '>=': return new FilterEqualOrGreaterThan($col, $op, $expression);
            case '<=': return new FilterEqualOrLessThan($col, $op, $expression);
            case '!=': return new FilterNotEqual($col, $op, $expression);
            default: throw new ProgrammingError(
                sprintf('There is no such filter sign: %s', $op)
            );
        }
    }

    /**
     * Or FilterOperator factory
     *
     * @param Filter $filter,...  Unlimited optional list of Filters
     *
     * @return FilterOr
     */
    public static function matchAny()
    {
        $args = func_get_args();
        if (count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }
        return new FilterOr($args);
    }

    /**
     * Or FilterOperator factory
     *
     * @param Filter $filter,...  Unlimited optional list of Filters
     *
     * @return FilterAnd
     */
    public static function matchAll()
    {
        $args = func_get_args();
        if (count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }
        return new FilterAnd($args);
    }

    /**
     * FilterNot factory, negates the given filter
     *
     * @param Filter $filter Filter to be negated
     *
     * @return FilterNot
     */
    public static function not()
    {
        $args = func_get_args();
        if (count($args) === 1) {
            if (is_array($args[0])) {
                $args = $args[0];
            }
        }
        return new FilterNot($args);
    }

    /**
     * Create filter from queryString
     *
     * This is still pretty basic, need improvement
     */
    public static function fromQueryString($query)
    {
        return FilterQueryString::parse($query);
    }
}
