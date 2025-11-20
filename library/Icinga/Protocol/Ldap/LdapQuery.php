<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Ldap;

use Icinga\Data\Filter\Filter;
use LogicException;
use Icinga\Data\SimpleQuery;

/**
 * LDAP query class
 */
class LdapQuery extends SimpleQuery
{
    /**
     * The base dn being used for this query
     *
     * @var string
     */
    protected $base;

    /**
     * Whether this query is permitted to utilize paged results
     *
     * @var bool
     */
    protected $usePagedResults;

    /**
     * The name of the attribute used to unfold the result
     *
     * @var string
     */
    protected $unfoldAttribute;

    /**
     * This query's native LDAP filter
     *
     * @var string
     */
    protected $nativeFilter;

    /**
     * Only fetch the entry at the base of the search
     */
    const SCOPE_BASE = 'base';

    /**
     * Fetch entries one below the base DN
     */
    const SCOPE_ONE = 'one';

    /**
     * Fetch all entries below the base DN
     */
    const SCOPE_SUB = 'sub';

    /**
     * All available scopes
     *
     * @var array
     */
    public static $scopes = array(
        LdapQuery::SCOPE_BASE,
        LdapQuery::SCOPE_ONE,
        LdapQuery::SCOPE_SUB
    );

    /**
     * LDAP search scope (default: SCOPE_SUB)
     *
     * @var string
     */
    protected $scope = LdapQuery::SCOPE_SUB;

    /**
     * Initialize this query
     */
    protected function init()
    {
        $this->usePagedResults = false;
    }

    /**
     * Set the base dn to be used for this query
     *
     * @param   string  $base
     *
     * @return  $this
     */
    public function setBase($base)
    {
        $this->base = $base;
        return $this;
    }

    /**
     * Return the base dn being used for this query
     *
     * @return  string
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * Set whether this query is permitted to utilize paged results
     *
     * @param   bool    $state
     *
     * @return  $this
     */
    public function setUsePagedResults($state = true)
    {
        $this->usePagedResults = (bool) $state;
        return $this;
    }

    /**
     * Return whether this query is permitted to utilize paged results
     *
     * @return  bool
     */
    public function getUsePagedResults()
    {
        return $this->usePagedResults;
    }

    /**
     * Set the attribute to be used to unfold the result
     *
     * @param   string  $attributeName
     *
     * @return  $this
     */
    public function setUnfoldAttribute($attributeName)
    {
        $this->unfoldAttribute = $attributeName;
        return $this;
    }

    /**
     * Return the attribute to use to unfold the result
     *
     * @return  string
     */
    public function getUnfoldAttribute()
    {
        return $this->unfoldAttribute;
    }

    /**
     * Set this query's native LDAP filter
     *
     * @param   string  $filter
     *
     * @return  $this
     */
    public function setNativeFilter($filter)
    {
        $this->nativeFilter = $filter;
        return $this;
    }

    /**
     * Return this query's native LDAP filter
     *
     * @return  string
     */
    public function getNativeFilter()
    {
        return $this->nativeFilter;
    }

    /**
     * Choose an objectClass and the columns you are interested in
     *
     * {@inheritdoc} This creates an objectClass filter.
     */
    public function from($target, ?array $fields = null)
    {
        $this->where('objectClass', $target);
        return parent::from($target, $fields);
    }

    public function where($condition, $value = null)
    {
        $this->addFilter(Filter::expression($condition, '=', $value));
        return $this;
    }

    public function addFilter(Filter $filter)
    {
        $this->makeCaseInsensitive($filter);
        return parent::addFilter($filter);
    }

    public function setFilter(Filter $filter)
    {
        $this->makeCaseInsensitive($filter);
        return parent::setFilter($filter);
    }

    protected function makeCaseInsensitive(Filter $filter)
    {
        if ($filter->isExpression()) {
            /** @var \Icinga\Data\Filter\FilterExpression $filter */
            $filter->setCaseSensitive(false);
        } else {
            /** @var \Icinga\Data\Filter\FilterChain $filter */
            foreach ($filter->filters() as $subFilter) {
                $this->makeCaseInsensitive($subFilter);
            }
        }
    }

    public function compare($a, $b, $orderIndex = 0)
    {
        if (array_key_exists($orderIndex, $this->order)) {
            $column = $this->order[$orderIndex][0];
            $direction = $this->order[$orderIndex][1];

            $flippedColumns = $this->flippedColumns ?: array_flip($this->columns);
            if (array_key_exists($column, $flippedColumns) && is_string($flippedColumns[$column])) {
                $column = $flippedColumns[$column];
            }

            if (is_array($a->$column)) {
                // rfc2891 states: If a sort key is a multi-valued attribute, and an entry happens to
                //   have multiple values for that attribute and no other controls are
                //   present that affect the sorting order, then the server SHOULD use the
                //   least value (according to the ORDERING rule for that attribute).
                $a = clone $a;
                $a->$column = array_reduce($a->$column, function ($carry, $item) use ($direction) {
                    $result = $carry === null ? 0 : strcmp($item, $carry);
                    return ($direction === self::SORT_ASC ? $result : $result * -1) < 1 ? $item : $carry;
                });
            }

            if (is_array($b->$column)) {
                $b = clone $b;
                $b->$column = array_reduce($b->$column, function ($carry, $item) use ($direction) {
                    $result = $carry === null ? 0 : strcmp($item, $carry);
                    return ($direction === self::SORT_ASC ? $result : $result * -1) < 1 ? $item : $carry;
                });
            }
        }

        return parent::compare($a, $b, $orderIndex);
    }

    /**
     * Fetch result as tree
     *
     * @return  Root
     *
     * @todo    This is untested waste, not being used anywhere and ignores the query's order and base dn.
     *           Evaluate whether it's reasonable to properly implement and test it.
     */
    public function fetchTree()
    {
        $result = $this->fetchAll();
        $sorted = array();
        $quotedDn = preg_quote($this->ds->getDn(), '/');
        foreach ($result as $key => & $item) {
            $new_key = LdapUtils::implodeDN(
                array_reverse(
                    LdapUtils::explodeDN(
                        preg_replace('/,' . $quotedDn . '$/', '', $key)
                    )
                )
            );
            $sorted[$new_key] = $key;
        }

        ksort($sorted);

        $tree = Root::forConnection($this->ds);
        $root_dn = $tree->getDN();
        foreach ($sorted as $sort_key => & $key) {
            if ($key === $root_dn) {
                continue;
            }
            $tree->createChildByDN($key, $result[$key]);
        }
        return $tree;
    }

    /**
     * Fetch the distinguished name of the first result
     *
     * @return  string|false    The distinguished name or false in case it's not possible to fetch a result
     *
     * @throws  LdapException   In case the query returns multiple results
     *                          (i.e. it's not possible to fetch a unique DN)
     */
    public function fetchDn()
    {
        return $this->ds->fetchDn($this);
    }

    /**
     * Render and return this query's filter
     *
     * @return  string
     */
    public function renderFilter()
    {
        $filter = $this->ds->renderFilter($this->filter);
        if ($this->nativeFilter) {
            $filter = '(&(' . $this->nativeFilter . ')' . $filter . ')';
        }

        return $filter;
    }

    /**
     * Return the LDAP filter to be applied on this query
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->renderFilter();
    }

    /**
     * Get LDAP search scope
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set LDAP search scope
     *
     * Valid: sub one base (Default: sub)
     *
     * @param  string          $scope
     *
     * @return LdapQuery
     *
     * @throws LogicException  If scope value is invalid
     */
    public function setScope($scope)
    {
        if (! in_array($scope, static::$scopes)) {
            throw new LogicException(
                'Can\'t set scope %d, it is is invalid. Use one of %s or LdapQuery\'s constants.',
                $scope,
                implode(', ', static::$scopes)
            );
        }
        $this->scope = $scope;
        return $this;
    }
}
