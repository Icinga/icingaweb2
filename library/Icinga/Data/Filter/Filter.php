<?php

namespace Icinga\Data\Filter;

use Exception;

/**
 * Filter
 *
 * Base class for filters (why?) and factory for the different FilterOperators
 */
class Filter
{
    protected $id = '1';

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getById($id)
    {
        if ($id === $this->getId()) {
            return $this;
        }
        throw new Exception(sprintf(
            'Trying to get invalid filter index "%s" from "%s"', $id, $this
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
        return new FilterWhere($col, $filter);
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
        return new FilterOr(func_get_args());
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
        return new FilterAnd(func_get_args());
    }

    /**
     * FilterNot factory, negates the given filter
     *
     * @param Filter $filter Filter to be negated
     *
     * @return FilterNot
     */
    public static function not(Filter $filter)
    {
        return new FilterNot(array($filter)); // ??
    }

    /**
     * Create filter from queryString
     *
     * This is still pretty basic, need improvement
     */
    public static function fromQueryString($query)
    {
        $query = rawurldecode($query);
        $parts = preg_split('~&~', $query, -1, PREG_SPLIT_NO_EMPTY);
        $filters = Filter::matchAll();
        foreach ($parts as $part) {
            self::parseQueryStringPart($part, $filters);
        }
        return $filters;
    }

    /**
     * Parse query string part
     *
     */
    protected static function parseQueryStringPart($part, & $filters)
    {
        $negations = 0;

        if (strpos($part, '=') === false) {

            $key = rawurldecode($part);

            while (substr($key, 0, 1) === '!') {
                if (strlen($key) < 2) {
                    throw new FilterException(
                        sprintf('Got invalid filter part: "%s"', $part)
                    );
                }
                $key = substr($key, 1);
                $negations++;
            }

            $filter = Filter::where($key, true);

        } else {
            list($key, $val) = preg_split('/=/', $part, 2);
            $key = rawurldecode($key);
            $val = rawurldecode($val);

            while (substr($key, 0, 1) === '!') {
                if (strlen($key) < 2) {
                    throw new FilterException(
                        sprintf('Got invalid filter part: "%s"', $part)
                    );
                }
                $key = substr($key, 1);
                $negations++;
            }

            while (substr($key, -1) === '!') {
                if (strlen($key) < 2) {
                    throw new FilterException(
                        sprintf('Got invalid filter part: "%s"', $part)
                    );
                }
                $key = substr($key, 0, -1);
                $negations++;
            }

            if (strpos($val, '|') !== false) {
                $vals = preg_split('/\|/', $val, -1, PREG_SPLIT_NO_EMPTY);
                $filter = Filter::matchAny();
                foreach ($vals as $val) {
                    $filter->addFilter(Filter::where($key, $val));
                }
            } else {
                $filter = Filter::where($key, $val);
            }

        }

        if ($negations % 2 === 0) {
            $filters->addFilter($filter);
        } else {
            $filters->addFilter(Filter::not($filter));
        }
    }

    /**
     * We need a new Querystring-Parser
     *
     * Still TBD, should be able to read such syntax:
     * (host_name=test&(service=ping|(service=http&host=*net*)))
     */
    protected static function consumeStringUnless(& $string, $stop)
    {
    }
}
