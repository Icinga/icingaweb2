<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Db;

use Exception;
use Zend_Db_Expr;
use Zend_Db_Select;
use Icinga\Application\Logger;
use Icinga\Data\Filter\FilterAnd;
use Icinga\Data\Filter\FilterChain;
use Icinga\Data\Filter\FilterNot;
use Icinga\Data\Filter\FilterOr;
use Icinga\Data\SimpleQuery;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\QueryException;

/**
 * Database query class
 */
class DbQuery extends SimpleQuery
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Whether or not the query is a sub query
     *
     * Sub queries are automatically wrapped in parentheses
     *
     * @var bool
     */
    protected $isSubQuery = false;

    /**
     * Select query
     *
     * @var Zend_Db_Select
     */
    protected $select;

    /**
     * Whether to use a subquery for counting
     *
     * When the query is distinct or has a HAVING or GROUP BY clause this must be set to true
     *
     * @var bool
     */
    protected $useSubqueryCount = false;

    /**
     * Count query result
     *
     * Count queries are only executed once
     *
     * @var int
     */
    protected $count;

    /**
     * GROUP BY clauses
     *
     * @var string|array
     */
    protected $group;

    protected function init()
    {
        $this->db = $this->ds->getDbAdapter();
        $this->select = $this->db->select();
        parent::init();
    }

    /**
     * Get whether or not the query is a sub query
     */
    public function getIsSubQuery()
    {
        return $this->isSubQuery;
    }

    /**
     * Set whether or not the query is a sub query
     *
     * @param   bool $isSubQuery
     *
     * @return  $this
     */
    public function setIsSubQuery($isSubQuery = true)
    {
        $this->isSubQuery = (bool) $isSubQuery;
        return $this;
    }

    public function setUseSubqueryCount($useSubqueryCount = true)
    {
        $this->useSubqueryCount = $useSubqueryCount;
        return $this;
    }

    public function from($target, array $fields = null)
    {
        parent::from($target, $fields);
        $this->select->from($this->target, array());
        return $this;
    }

    public function where($condition, $value = null)
    {
        // $this->count = $this->select = null;
        return parent::where($condition, $value);
    }

    protected function dbSelect()
    {
        return clone $this->select;
    }

    /**
     * Return the underlying select
     *
     * @return  Zend_Db_Select
     */
    public function select()
    {
        return $this->select;
    }

    /**
     * Get the select query
     *
     * Applies order and limit if any
     *
     * @return Zend_Db_Select
     */
    public function getSelectQuery()
    {
        $select = $this->dbSelect();
        // Add order fields to select for postgres distinct queries (#6351)
        if ($this->hasOrder()
            && $this->getDatasource()->getDbType() === 'pgsql'
            && $select->getPart(Zend_Db_Select::DISTINCT) === true) {
            foreach ($this->getOrder() as $fieldAndDirection) {
                if (array_search($fieldAndDirection[0], $this->columns, true) === false) {
                    $this->columns[] = $fieldAndDirection[0];
                }
            }
        }

        $group = $this->getGroup();
        if ($group) {
            $select->group($group);
        }

        if (! empty($this->columns)) {
            $select->columns($this->columns);
        }

        $this->applyFilterSql($select);

        if ($this->hasLimit() || $this->hasOffset()) {
            $select->limit($this->getLimit(), $this->getOffset());
        }
        if ($this->hasOrder()) {
            foreach ($this->getOrder() as $fieldAndDirection) {
                $select->order(
                    $fieldAndDirection[0] . ' ' . $fieldAndDirection[1]
                );
            }
        }

        return $select;
    }

    protected function applyFilterSql($select)
    {
        $where = $this->renderFilter($this->filter);
        if ($where !== '') {
            $select->where($where);
        }
    }

    /**
     * @deprecated  Use DbConnection::renderFilter() instead!
     */
    protected function renderFilter($filter, $level = 0)
    {
        $str = '';
        if ($filter instanceof FilterChain) {
            if ($filter instanceof FilterAnd) {
                $op = ' AND ';
            } elseif ($filter instanceof FilterOr) {
                $op = ' OR ';
            } elseif ($filter instanceof FilterNot) {
                $op = ' AND ';
                $str .= ' NOT ';
            } else {
                throw new QueryException(
                    'Cannot render filter: %s',
                    $filter
                );
            }
            $parts = array();
            if (! $filter->isEmpty()) {
                foreach ($filter->filters() as $f) {
                    $filterPart = $this->renderFilter($f, $level + 1);
                    if ($filterPart !== '') {
                        $parts[] = $filterPart;
                    }
                }
                if (! empty($parts)) {
                    if ($level > 0) {
                        $str .= ' (' . implode($op, $parts) . ') ';
                    } else {
                        $str .= implode($op, $parts);
                    }
                }
            }
        } else {
            $str .= $this->whereToSql($filter->getColumn(), $filter->getSign(), $filter->getExpression());
        }

        return $str;
    }

    protected function escapeForSql($value)
    {
        // bindParam? bindValue?
        if (is_array($value)) {
            $ret = array();
            foreach ($value as $val) {
                $ret[] = $this->escapeForSql($val);
            }
            return implode(', ', $ret);
        } else {
            //if (preg_match('/^\d+$/', $value)) {
            //    return $value;
            //} else {
                return $this->db->quote($value);
            //}
        }
    }

    protected function escapeWildcards($value)
    {
        return preg_replace('/\*/', '%', $value);
    }

    protected function valueToTimestamp($value)
    {
        // We consider integers as valid timestamps. Does not work for URL params
        if (ctype_digit($value)) {
            return $value;
        }
        $value = strtotime($value);
        if (! $value) {
            /*
            NOTE: It's too late to throw exceptions, we might finish in __toString
            throw new QueryException(sprintf(
                '"%s" is not a valid time expression',
                $value
            ));
            */
        }
        return $value;
    }

    protected function timestampForSql($value)
    {
        // TODO: do this db-aware
        return $this->escapeForSql(date('Y-m-d H:i:s', $value));
    }

    /**
     * Check for timestamp fields
     *
     * TODO: This is not here to do automagic timestamp stuff. One may
     *       override this function for custom voodoo, IdoQuery right now
     *       does. IMO we need to split whereToSql functionality, however
     *       I'd prefer to wait with this unless we understood how other
     *       backends will work. We probably should also rename this
     *       function to isTimestampColumn().
     *
     * @param  string $field Field Field name to checked
     * @return bool          Whether this field expects timestamps
     */
    public function isTimestamp($field)
    {
        return false;
    }

    public function whereToSql($col, $sign, $expression)
    {
        if ($this->isTimestamp($col)) {
            $expression = $this->valueToTimestamp($expression);
        }

        if (is_array($expression)) {
            if ($sign === '=') {
                return $col . ' IN (' . $this->escapeForSql($expression) . ')';
            } elseif ($sign === '!=') {
                return sprintf('(%1$s NOT IN (%2$s) OR %1$s IS NULL)', $col, $this->escapeForSql($expression));
            }

            throw new QueryException('Unable to render array expressions with operators other than equal or not equal');
        } elseif ($sign === '=' && strpos($expression, '*') !== false) {
            if ($expression === '*') {
                return new Zend_Db_Expr('TRUE');
            }

            return $col . ' LIKE ' . $this->escapeForSql($this->escapeWildcards($expression));
        } elseif ($sign === '!=' && strpos($expression, '*') !== false) {
            if ($expression === '*') {
                return new Zend_Db_Expr('FALSE');
            }

            return sprintf(
                '(%1$s NOT LIKE %2$s OR %1$s IS NULL)',
                $col,
                $this->escapeForSql($this->escapeWildcards($expression))
            );
        } elseif ($sign === '!=') {
            return sprintf('(%1$s %2$s %3$s OR %1$s IS NULL)', $col, $sign, $this->escapeForSql($expression));
        } else {
            return sprintf('%s %s %s', $col, $sign, $this->escapeForSql($expression));
        }
    }

    /**
     * Get the count query
     *
     * @return Zend_Db_Select
     */
    public function getCountQuery()
    {
        // TODO: there may be situations where we should clone the "select"
        $count = $this->dbSelect();
        $this->applyFilterSql($count);
        $group = $this->getGroup();
        if ($this->useSubqueryCount || $group) {
            if (! empty($this->columns)) {
                $count->columns($this->columns);
            }
            if ($group) {
                $count->group($group);
            }
            $columns = array('cnt' => 'COUNT(*)');
            return $this->db->select()->from($count, $columns);
        }

        $count->columns(array('cnt' => 'COUNT(*)'));
        return $count;
    }

    /**
     * Count all rows of the result set
     *
     * @return int
     */
    public function count()
    {
        if ($this->count === null) {
            $this->count = parent::count();
        }

        return $this->count;
    }

    /**
     * Return the select and count query as a textual representation
     *
     * @return string A string containing the select and count query, using unix style newlines as linebreaks
     */
    public function dump()
    {
        return "QUERY\n=====\n"
        . $this->getSelectQuery()
        . "\n\nCOUNT\n=====\n"
        . $this->getCountQuery()
        . "\n\n";
    }

    public function __clone()
    {
        parent::__clone();
        $this->select = clone $this->select;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            $select = (string) $this->getSelectQuery();
            return $this->getIsSubQuery() ? ('(' . $select . ')') : $select;
        } catch (Exception $e) {
            Logger::debug('Failed to render DbQuery. An error occured: %s', $e);
            return '';
        }
    }

    /**
     * Add a GROUP BY clause
     *
     * @param   string|array $group
     *
     * @return  $this
     */
    public function group($group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Return the GROUP BY clause
     *
     * @return  string|array
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Return whether the given table has been joined
     *
     * @param   string  $table
     *
     * @return  bool
     */
    public function hasJoinedTable($table)
    {
        $fromPart = $this->select->getPart(Zend_Db_Select::FROM);
        if (isset($fromPart[$table])) {
            return true;
        }

        foreach ($fromPart as $options) {
            if ($options['tableName'] === $table && $options['joinType'] !== Zend_Db_Select::FROM) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the alias used for joining the given table
     *
     * @param   string      $table
     *
     * @return  string|null         null in case no alias is being used
     *
     * @throws  ProgrammingError    In case the given table has not been joined
     */
    public function getJoinedTableAlias($table)
    {
        $fromPart = $this->select->getPart(Zend_Db_Select::FROM);
        if (isset($fromPart[$table])) {
            if ($fromPart[$table]['joinType'] === Zend_Db_Select::FROM) {
                throw new ProgrammingError('Table "%s" has not been joined', $table);
            }

            return; // No alias in use
        }

        foreach ($fromPart as $alias => $options) {
            if ($options['tableName'] === $table && $options['joinType'] !== Zend_Db_Select::FROM) {
                return $alias;
            }
        }

        throw new ProgrammingError('Table "%s" has not been joined', $table);
    }

    /**
     * Add an INNER JOIN table and colums to the query
     *
     * @param   array|string|Zend_Db_Expr   $name   The table name
     * @param   string                      $cond   Join on this condition
     * @param   array|string                $cols   The columns to select from the joined table
     * @param   string                      $schema The database name to specify, if any
     *
     * @return  $this
     */
    public function join($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
    {
        $this->select->joinInner($name, $cond, $cols, $schema);
        return $this;
    }

    /**
     * Add an INNER JOIN table and colums to the query
     *
     * @param   array|string|Zend_Db_Expr   $name   The table name
     * @param   string                      $cond   Join on this condition
     * @param   array|string                $cols   The columns to select from the joined table
     * @param   string                      $schema The database name to specify, if any
     *
     * @return  $this
     */
    public function joinInner($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
    {
        $this->select->joinInner($name, $cond, $cols, $schema);
        return $this;
    }

    /**
     * Add a LEFT OUTER JOIN table and colums to the query
     *
     * @param   array|string|Zend_Db_Expr   $name   The table name
     * @param   string                      $cond   Join on this condition
     * @param   array|string                $cols   The columns to select from the joined table
     * @param   string                      $schema The database name to specify, if any
     *
     * @return  $this
     */
    public function joinLeft($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
    {
        $this->select->joinLeft($name, $cond, $cols, $schema);
        return $this;
    }

    /**
     * Add a RIGHT OUTER JOIN table and colums to the query
     *
     * @param   array|string|Zend_Db_Expr   $name   The table name
     * @param   string                      $cond   Join on this condition
     * @param   array|string                $cols   The columns to select from the joined table
     * @param   string                      $schema The database name to specify, if any
     *
     * @return  $this
     */
    public function joinRight($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
    {
        $this->select->joinRight($name, $cond, $cols, $schema);
        return $this;
    }

    /**
     * Add a FULL OUTER JOIN table and colums to the query
     *
     * @param   array|string|Zend_Db_Expr   $name   The table name
     * @param   string                      $cond   Join on this condition
     * @param   array|string                $cols   The columns to select from the joined table
     * @param   string                      $schema The database name to specify, if any
     *
     * @return  $this
     */
    public function joinFull($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
    {
        $this->select->joinFull($name, $cond, $cols, $schema);
        return $this;
    }

    /**
     * Add a CROSS JOIN table and colums to the query
     *
     * @param   array|string|Zend_Db_Expr   $name   The table name
     * @param   array|string                $cols   The columns to select from the joined table
     * @param   string                      $schema The database name to specify, if any
     *
     * @return  $this
     */
    public function joinCross($name, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
    {
        $this->select->joinCross($name, $cols, $schema);
        return $this;
    }

    /**
     * Add a NATURAL JOIN table and colums to the query
     *
     * @param   array|string|Zend_Db_Expr   $name   The table name
     * @param   array|string                $cols   The columns to select from the joined table
     * @param   string                      $schema The database name to specify, if any
     *
     * @return  $this
     */
    public function joinNatural($name, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
    {
        $this->select->joinNatural($name, $cols, $schema);
        return $this;
    }

    /**
     * Add a UNION clause to the query
     *
     * @param   array   $select     Select clauses for the union
     * @param   string  $type       Type of UNION to use
     *
     * @return  $this
     */
    public function union($select = array(), $type = Zend_Db_Select::SQL_UNION)
    {
        $this->select->union($select, $type);
        return $this;
    }
}
