<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Ldap;

use Icinga\Data\SimpleQuery;
use Icinga\Data\Filter\Filter;
use Icinga\Exception\NotImplementedError;

/**
 * LDAP query class
 */
class Query extends SimpleQuery
{
    /**
     * This query's filters
     *
     * Currently just a basic key/value pair based array. Can be removed once Icinga\Data\Filter is supported.
     *
     * @var array
     */
    protected $filters;

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
     * Initialize this query
     */
    protected function init()
    {
        $this->filters = array();
        $this->usePagedResults = true;
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
     * Choose an objectClass and the columns you are interested in
     *
     * {@inheritdoc} This creates an objectClass filter.
     */
    public function from($target, array $fields = null)
    {
        $this->filters['objectClass'] = $target;
        return parent::from($target, $fields);
    }

    /**
     * Add a new filter to the query
     *
     * @param   string      $condition  Column to search in
     * @param   mixed       $value      Value to look for (asterisk wildcards are allowed)
     *
     * @return  $this
     */
    public function where($condition, $value = null)
    {
        // TODO: Adjust this once support for Icinga\Data\Filter is available
        if ($condition instanceof Expression) {
            $this->filters[] = $condition;
        } else {
            $this->filters[$condition] = $value;
        }

        return $this;
    }

    public function getFilter()
    {
        throw new NotImplementedError('Support for Icinga\Data\Filter is still missing. Use $this->where() instead');
    }

    public function addFilter(Filter $filter)
    {
        // TODO: This should be considered a quick fix only.
        //       Drop this entirely once support for Icinga\Data\Filter is available
        if ($filter->isExpression()) {
            $this->where($filter->getColumn(), $filter->getExpression());
        } elseif ($filter->isChain()) {
            foreach ($filter->filters() as $chainOrExpression) {
                $this->addFilter($chainOrExpression);
            }
        }
    }

    public function setFilter(Filter $filter)
    {
        throw new NotImplementedError('Support for Icinga\Data\Filter is still missing. Use $this->where() instead');
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
        $quotedDn = preg_quote($this->connection->getDN(), '/');
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
        unset($groups);
        ksort($sorted);

        $tree = Root::forConnection($this->connection);
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
     * @throws  Exception       In case the query returns multiple results
     *                          (i.e. it's not possible to fetch a unique DN)
     */
    public function fetchDn()
    {
        return $this->ds->fetchDn($this);
    }

    /**
     * Return the LDAP filter to be applied on this query
     *
     * @return  string
     *
     * @throws  Exception   In case the objectClass filter does not exist
     */
    protected function renderFilter()
    {
        if (! isset($this->filters['objectClass'])) {
            throw new Exception('Object class is mandatory');
        }

        $parts = array();
        foreach ($this->filters as $key => $value) {
            if ($value instanceof Expression) {
                $parts[] = (string) $value;
            } else {
                $parts[] = sprintf(
                    '%s=%s',
                    LdapUtils::quoteForSearch($key),
                    LdapUtils::quoteForSearch($value, true)
                );
            }
        }

        if (count($parts) > 1) {
            return '(&(' . implode(')(', $parts) . '))';
        } else {
            return '(' . $parts[0] . ')';
        }
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
}
