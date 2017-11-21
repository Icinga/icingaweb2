<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

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

    abstract public function isExpression();

    abstract public function isChain();

    abstract public function isEmpty();

    abstract public function toQueryString();

    abstract public function andFilter(Filter $filter);

    abstract public function orFilter(Filter $filter);

    /**
     * Whether the give row matches this Filter
     *
     * @param mixed $row Preferrably an stdClass instance
     * @return bool
     */
    abstract public function matches($row);

    public function getUrlParams()
    {
        return UrlParams::fromQueryString($this->toQueryString());
    }

    public function getById($id)
    {
        if ((string) $id === $this->getId()) {
            return $this;
        }
        throw new ProgrammingError(
            'Trying to get invalid filter index "%s" from "%s" ("%s")',
            $id,
            $this,
            $this->id
        );
    }

    public function getId()
    {
        return $this->id;
    }

    public function isRootNode()
    {
        return false === strpos($this->id, '-');
    }

    abstract public function listFilteredColumns();

    public function applyChanges($changes)
    {
        $filter = $this;
        $pairs = array();
        foreach ($changes as $k => $v) {
            if (preg_match('/^(column|value|sign|operator)_([\d-]+)$/', $k, $m)) {
                $pairs[$m[2]][$m[1]] = $v;
            }
        }
        $operators = array();
        foreach ($pairs as $id => $fs) {
            if (array_key_exists('operator', $fs)) {
                $operators[$id] = $fs['operator'];
            } else {
                $f = $filter->getById($id);
                $f->setColumn($fs['column']);
                if ($f->getSign() !== $fs['sign']) {
                    if ($f->isRootNode()) {
                        $filter = $f->setSign($fs['sign']);
                    } else {
                        $filter->replaceById($id, $f->setSign($fs['sign']));
                    }
                }
                $f->setExpression($fs['value']);
            }
        }

        krsort($operators, version_compare(PHP_VERSION, '5.4.0') >= 0 ? SORT_NATURAL : SORT_REGULAR);
        foreach ($operators as $id => $operator) {
            $f = $filter->getById($id);
            if ($f->getOperatorName() !== $operator) {
                if ($f->isRootNode()) {
                    $filter = $f->setOperatorName($operator);
                } else {
                    $filter->replaceById($id, $f->setOperatorName($operator));
                }
            }
        }

        return $filter;
    }

    public function getParentId()
    {
        if ($this->isRootNode()) {
            throw new ProgrammingError('Filter root nodes have no parent');
        }
        return substr($this->id, 0, strrpos($this->id, '-'));
    }

    public function getParent()
    {
        return $this->getById($this->getParentId());
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
     * @return FilterExpression
     */
    public static function where($col, $filter)
    {
        return new FilterExpression($col, '=', $filter);
    }

    public static function expression($col, $op, $expression)
    {
        switch ($op) {
            case '=':
                return new FilterMatch($col, $op, $expression);
            case '<':
                return new FilterLessThan($col, $op, $expression);
            case '>':
                return new FilterGreaterThan($col, $op, $expression);
            case '>=':
                return new FilterEqualOrGreaterThan($col, $op, $expression);
            case '<=':
                return new FilterEqualOrLessThan($col, $op, $expression);
            case '!=':
                return new FilterMatchNot($col, $op, $expression);
            default:
                throw new ProgrammingError(
                    'There is no such filter sign: %s',
                    $op
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
        if (count($args) > 1) {
            return new FilterNot(array(new FilterAnd($args)));
        } else {
            return new FilterNot($args);
        }
    }

    public static function chain($operator, $filters = array())
    {
        switch ($operator) {
            case 'AND':
                return self::matchAll($filters);
            case 'OR':
                return self::matchAny($filters);
            case 'NOT':
                return self::not($filters);
        }
        throw new ProgrammingError(
            '"%s" is not a valid filter chain operator',
            $operator
        );
    }

    /**
     * Create filter from queryString
     *
     * This is still pretty basic, need improvement
     *
     * @return static
     */
    public static function fromQueryString($query)
    {
        return FilterQueryString::parse($query);
    }
}
