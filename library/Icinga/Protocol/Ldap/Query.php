<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Ldap;

/**
 * Search class
 *
 * @package Icinga\Protocol\Ldap
 */
/**
 * Search abstraction class
 *
 * Usage example:
 *
 * <code>
 * $connection->select()->from('user')->where('sAMAccountName = ?', 'icinga');
 * </code>
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package    Icinga\Protocol\Ldap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Query
{
    protected $connection;
    protected $filters = array();
    protected $fields = array();
    protected $limit_count = 0;
    protected $limit_offset = 0;
    protected $sort_columns = array();
    protected $count;
    protected $base;
    protected $usePagedResults = true;

    /**
     * Constructor
     *
     * @param Connection LDAP Connection object
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function setBase($base)
    {
        $this->base = $base;
        return $this;
    }

    public function hasBase()
    {
        return $this->base !== null;
    }

    public function getBase()
    {
        return $this->base;
    }

    public function setUsePagedResults($state = true)
    {
        $this->usePagedResults = (bool) $state;
        return $this;
    }

    public function getUsePagedResults()
    {
        return $this->usePagedResults;
    }

    /**
     * Count result set, ignoring limits
     *
     * @return int
     */
    public function count()
    {
        if ($this->count === null) {
            $this->count = $this->connection->count($this);
        }
        return $this->count;
    }

    /**
     * Count result set, ignoring limits
     *
     * @return int
     */
    public function limit($count = null, $offset = null)
    {
        if (! preg_match('~^\d+~', $count . $offset)) {
            throw new Exception(
                'Got invalid limit: %s, %s',
                $count,
                $offset
            );
        }
        $this->limit_count  = (int) $count;
        $this->limit_offset = (int) $offset;
        return $this;
    }

    /**
     * Whether a limit has been set
     *
     * @return boolean
     */
    public function hasLimit()
    {
        return $this->limit_count > 0;
    }

    /**
     * Whether an offset (limit) has been set
     *
     * @return boolean
     */
    public function hasOffset()
    {
        return $this->limit_offset > 0;
    }

    /**
     * Retrieve result limit
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit_count;
    }

    /**
     * Retrieve result offset
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->limit_offset;
    }

    /**
     * Fetch result as tree
     *
     * @return Node
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
     * Fetch result as an array of objects
     *
     * @return array
     */
    public function fetchAll()
    {
        return $this->connection->fetchAll($this);
    }

    /**
     * Fetch first result row
     *
     * @return object
     */
    public function fetchRow()
    {
        return $this->connection->fetchRow($this);
    }

    /**
     * Fetch first column value from first result row
     *
     * @return mixed
     */
    public function fetchOne()
    {
        return $this->connection->fetchOne($this);
    }

    /**
     * Fetch a key/value list, first column is key, second is value
     *
     * @return array
     */
    public function fetchPairs()
    {
        // STILL TODO!!
        return $this->connection->fetchPairs($this);
    }

    /**
     * Where to select (which fields) from
     *
     * This creates an objectClass filter
     *
     * @return Query
     */
    public function from($objectClass, $fields = array())
    {
        $this->filters['objectClass'] = $objectClass;
        $this->fields = $fields;
        return $this;
    }

    /**
     * Add a new filter to the query
     *
     * @param  string  Column to search in
     * @param  string  Filter text (asterisks are allowed)
     * @return Query
     */
    public function where($key, $val)
    {
        $this->filters[$key] = $val;
        return $this;
    }

    /**
     * Sort by given column
     *
     * TODO: Sort direction is not implemented yet
     *
     * @param  string  Order column
     * @param  string  Order direction
     * @return Query
     */
    public function order($column, $direction = 'ASC')
    {
        $this->sort_columns[] = array($column, $direction);
        return $this;
    }

    /**
     * Retrieve a list of the desired fields
     *
     * @return array
     */
    public function listFields()
    {
        return $this->fields;
    }

    /**
     * Retrieve a list containing current sort columns
     *
     * @return array
     */
    public function getSortColumns()
    {
        return $this->sort_columns;
    }

    /**
     * Return a pagination adapter for the current query
     *
     * @return \Zend_Paginator
     */
    public function paginate($limit = null, $page = null)
    {
        if ($page === null || $limit === null) {
            $request = \Zend_Controller_Front::getInstance()->getRequest();
            if ($page === null) {
                $page = $request->getParam('page', 0);
            }
            if ($limit === null) {
                $limit = $request->getParam('limit', 20);
            }
        }
        $paginator = new \Zend_Paginator(
            // TODO: Adapter doesn't fit yet:
            new \Icinga\Web\Paginator\Adapter\QueryAdapter($this)
        );
        $paginator->setItemCountPerPage($limit);
        $paginator->setCurrentPageNumber($page);
        return $paginator;
    }

    /**
     * Add a filter expression to this query
     *
     * @param   Expression  $expression
     *
     * @return  Query
     */
    public function addFilter(Expression $expression)
    {
        $this->filters[] = $expression;
        return $this;
    }

    /**
     * Returns the LDAP filter that will be applied
     *
     * @string
     */
    public function create()
    {
        $parts = array();
        if (! isset($this->filters['objectClass']) || $this->filters['objectClass'] === null) {
            throw new Exception('Object class is mandatory');
        }
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

    public function __toString()
    {
        return $this->create();
    }

    /**
     * Descructor
     */
    public function __destruct()
    {
        // To be on the safe side:
        unset($this->connection);
    }
}
