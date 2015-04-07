<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Exception\IcingaException;
use Icinga\Application\Logger;
use Icinga\Data\Db\DbQuery;
use Icinga\Exception\ProgrammingError;
use Icinga\Application\Icinga;
use Icinga\Web\Session;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;

/**
 * Base class for Ido Queries
 *
 * This is the base class for all Ido queries and should be extended for new queries
 * The starting point for implementations is the columnMap attribute. This is an asscociative array in the
 * following form:
 *
 * <pre>
 * <code>
 * array(
 *      'virtualTable' => array(
 *          'fieldalias1' => 'queryColumn1',
 *          'fieldalias2' => 'queryColumn2',
 *          ....
 *      ),
 *      'virtualTable2' => array(
 *          'host'       =>  'host_name1'
 *      )
 * )
 * </code>
 * </pre>
 *
 * This allows you to select e.g. fieldalias1, which automatically calls the query code for joining 'virtualTable'. If
 * you afterwards select 'host', 'virtualTable2' will be joined. The joining logic is up to you, in order to make the
 * above example work you need to implement the joinVirtualTable() method which contain your
 * custom (Zend_Db) logic for joining, filtering and querying the data you want.
 *
 */
abstract class IdoQuery extends DbQuery
{
    /**
     * The prefix to use
     *
     * @var String
     */
    protected $prefix;

    /**
     * The alias name for the index column
     *
     * @var String
     */
    protected $idxAliasColumn;

    /**
     * The table containing the index column alias
     *
     * @var String
     */
    protected $idxAliasTable;

    /**
     * The column map containing all filterable columns
     *
     * This must be overwritten by child classes, in the format
     * array(
     *      'virtualTable' => array(
     *          'fieldalias1' => 'queryColumn1',
     *          'fieldalias2' => 'queryColumn2',
     *          ....
     *      )
     * )
     *
     * @var array
     */
    protected $columnMap = array();

    /**
     * Custom vars available for this query
     *
     * @var array
     */
    protected $customVars = array();

    /**
     * An array with all 'virtual' tables that are already joined
     *
     * Virtual tables are the keys  of the columnMap array and require a
     * join%VirtualTableName%() method to be defined in the concrete
     * query
     *
     * @var array
     */
    protected $joinedVirtualTables = array();

    /**
     * The primary field name for the object table
     *
     * @var string
     */
    protected $object_id       = 'object_id';

    /**
     * The primary field name for the IDO host table
     *
     * @var string
     */
    protected $host_id         = 'host_id';

    /**
     * The primary field name for the IDO hostgroup table
     *
     * @var string
     */
    protected $hostgroup_id    = 'hostgroup_id';

    /**
     * The primary field name for the IDO service table
     *
     * @var string
     */
    protected $service_id      = 'service_id';

    /**
     * The primary field name for the IDO serviegroup table
     *
     * @var string
     */
    protected $servicegroup_id = 'servicegroup_id';

    /**
     * The primary field name for the IDO contact table
     *
     * @var string
     */
    protected $contact_id      = 'contact_id';

    /**
     * The primary field name for the IDO contactgroup table
     *
     * @var string
     */
    protected $contactgroup_id = 'contactgroup_id';

    /**
     * An array containing Column names that cause an aggregation of the query
     *
     * @var array
     */
    protected $aggregateColumnIdx = array();

    /**
     * True to allow customvar filters and queries
     *
     * @var bool
     */
    protected $allowCustomVars = false;

    /**
     * Current IDO version. This is bullshit and needs to be moved somewhere
     * else. As someone decided that we need no Backend-specific connection
     * class unfortunately there is no better place right now. And as of the
     * 'check_source' patch we need a quick fix immediately. So here you go.
     *
     * TODO: Fix this.
     *
     * @var string
     */
    protected static $idoVersion;

    /**
     * List of columns where the COLLATE SQL-instruction has been removed
     *
     * This list is being populated in case of a PostgreSQL backend only,
     * to ensure case-insensitive string comparison in WHERE clauses.
     *
     * @var array
     */
    protected $columnsWithoutCollation = array();

    /**
     * Return true when the column is an aggregate column
     *
     * @param  String $column       The column to test
     * @return bool                 True when the column is an aggregate column
     */
    public function isAggregateColumn($column)
    {
        return array_key_exists($column, $this->aggregateColumnIdx);
    }

    /**
     * Order the result by the given column
     *
     * @param string $columnOrAlias         The column or column alias to order by
     * @param int $dir                      The sort direction or null to use default direction
     *
     * @return $this                         Fluent interface
     */
    public function order($columnOrAlias, $dir = null)
    {
        $this->requireColumn($columnOrAlias);
        if ($this->isCustomvar($columnOrAlias)) {
            $columnOrAlias = $this->getCustomvarColumnName($columnOrAlias);
        } elseif ($this->hasAliasName($columnOrAlias)) {
            $columnOrAlias = $this->aliasToColumnName($columnOrAlias);
        } else {
            Logger::info('Can\'t order by column ' . $columnOrAlias);
            return $this;
        }
        return parent::order($columnOrAlias, $dir);
    }

    /**
     * Return true when the given field can be used for filtering
     *
     * @param String $field     The field to test
     * @return bool             True when the field can be used for querying, otherwise false
     */
    public function isValidFilterTarget($field)
    {
        return $this->getMappedField($field) !== null;
    }

    /**
     * Return the resolved field for an alias
     *
     * @param  String $field     The alias to resolve
     * @return String           The resolved alias or null if unknown
     */
    public function getMappedField($field)
    {
        foreach ($this->columnMap as $columnSource => $columnSet) {
            if (isset($columnSet[$field])) {
                return $columnSet[$field];
            }
        }
        if ($this->isCustomVar($field)) {
            return $this->getCustomvarColumnName($field);
        }
        return null;
    }

    public function distinct()
    {
        $this->select->distinct();
        return $this;
    }

    protected function requireFilterColumns(Filter $filter)
    {
        if ($filter instanceof FilterExpression) {
            $col = $filter->getColumn();
            $this->requireColumn($col);

            if ($this->isCustomvar($col)) {
                $col = $this->getCustomvarColumnName($col);
            } else {
                $col = $this->aliasToColumnName($col);
            }

            $filter->setColumn($col);
        } else {
            foreach ($filter->filters() as $filter) {
                $this->requireFilterColumns($filter);
            }
        }
    }

    public function addFilter(Filter $filter)
    {
        $this->requireFilterColumns($filter);
        parent::addFilter($filter);
    }

    /**
     * Recurse the given filter and ensure that any string conversion is case-insensitive
     *
     * @param Filter $filter
     */
    protected function lowerColumnsWithoutCollation(Filter $filter)
    {
        if ($filter instanceof FilterExpression) {
            if (
                in_array($filter->getColumn(), $this->columnsWithoutCollation)
                && strpos($filter->getColumn(), 'LOWER') !== 0
            ) {
                $filter->setColumn('LOWER(' . $filter->getColumn() . ')');
                $expression = $filter->getExpression();
                if (is_array($expression)) {
                    $filter->setExpression(array_map('strtolower', $expression));
                } else {
                    $filter->setExpression(strtolower($expression));
                }
            }
        } else {
            foreach ($filter->filters() as $chainedFilter) {
                $this->lowerColumnsWithoutCollation($chainedFilter);
            }
        }
    }

    protected function applyFilterSql($select)
    {
        if (! empty($this->columnsWithoutCollation)) {
            $this->lowerColumnsWithoutCollation($this->filter);
        }

        parent::applyFilterSql($select);
    }

    public function where($condition, $value = null)
    {
        $this->requireColumn($condition);
        $col = $this->getMappedField($condition);
        if ($col === null) {
            throw new IcingaException(
                'No such field: %s',
                $condition
            );
        }
        return parent::where($col, $value);
    }

    /**
     * Return true if an field contains an explicit timestamp
     *
     * @param  String $field    The field to test for containing an timestamp
     * @return bool             True when the field represents an timestamp
     */
    public function isTimestamp($field)
    {
        $mapped = $this->getMappedField($field);
        if ($mapped === null) {
            return stripos($field, 'UNIX_TIMESTAMP') !== false;
        }
        return stripos($mapped, 'UNIX_TIMESTAMP') !== false;
    }

    /**
     * Apply oracle specific query initialization
     */
    private function initializeForOracle()
    {
        // Oracle uses the reserved field 'id' for primary keys, so
        // these must be used instead of the normally defined ids
        $this->object_id = $this->host_id = $this->service_id
            = $this->hostgroup_id = $this->servicegroup_id
            = $this->contact_id = $this->contactgroup_id = 'id';
        foreach ($this->columnMap as &$columns) {
            foreach ($columns as &$value) {
                $value = preg_replace('/UNIX_TIMESTAMP/', 'localts2unixts', $value);
                $value = preg_replace('/ COLLATE .+$/', '', $value);
            }
        }
    }

    /**
     * Apply postgresql specific query initialization
     */
    private function initializeForPostgres()
    {
        foreach ($this->columnMap as $table => & $columns) {
            foreach ($columns as $key => & $value) {
                $value = preg_replace('/ COLLATE .+$/', '', $value, -1, $count);
                if ($count > 0) {
                    $this->columnsWithoutCollation[] = $this->getMappedField($key);
                }
                $value = preg_replace('/inet_aton\(([[:word:].]+)\)/i', '$1::inet - \'0.0.0.0\'', $value);
                $value = preg_replace(
                    '/UNIX_TIMESTAMP(\((?>[^()]|(?-1))*\))/i',
                    'CASE WHEN ($1 < \'1970-01-03 00:00:00+00\'::timestamp with time zone) THEN 0 ELSE UNIX_TIMESTAMP($1) END',
                    $value
                );
            }
        }
    }

    /**
     * Set up this query and join the initial tables
     *
     * @see IdoQuery::initializeForPostgres     For postgresql specific setup
     */
    protected function init()
    {
        parent::init();
        $this->prefix = $this->ds->getTablePrefix();

        if ($this->ds->getDbType() === 'oracle') {
            $this->initializeForOracle();
        } elseif ($this->ds->getDbType() === 'pgsql') {
            $this->initializeForPostgres();
        }
        $this->dbSelect();

        $this->select->columns($this->columns);
        //$this->joinBaseTables();
        $this->prepareAliasIndexes();
    }

    protected function dbSelect()
    {
        if ($this->select === null) {
            $this->select = $this->db->select();
            $this->joinBaseTables();
        }
        return clone $this->select;
    }

    /**
     * Join the base tables for this query
     */
    protected function joinBaseTables()
    {
        reset($this->columnMap);
        $table = key($this->columnMap);

        $this->select->from(
            array($table => $this->prefix . $table),
            array()
        );

        $this->joinedVirtualTables = array($table => true);
    }

    /**
     * Populates the idxAliasTAble and idxAliasColumn properties
     */
    protected function prepareAliasIndexes()
    {
        foreach ($this->columnMap as $tbl => & $cols) {
            foreach ($cols as $alias => $col) {
                $this->idxAliasTable[$alias] = $tbl;
                $this->idxAliasColumn[$alias] = preg_replace('~\n\s*~', ' ', $col);
            }
        }
    }

    /**
     * Resolve columns aliases to their database field using the columnMap
     *
     * @param   array $columns
     *
     * @return  array
     */
    public function resolveColumns($columns)
    {
        $resolvedColumns = array();

        foreach ($columns as $alias => $col) {
            $this->requireColumn($col);
            if ($this->isCustomvar($col)) {
                $name = $this->getCustomvarColumnName($col);
            } else {
                $name = $this->aliasToColumnName($col);
            }
            if (is_int($alias)) {
                $alias = $col;
            }

            $resolvedColumns[$alias] = preg_replace('|\n|', ' ', $name);
        }

        return $resolvedColumns;
    }

    /**
     * Return all columns that will be selected when no columns are given in the constructor or from
     *
     * @return array        An array of column aliases
     */
    public function getDefaultColumns()
    {
        reset($this->columnMap);
        $table = key($this->columnMap);
        return array_keys($this->columnMap[$table]);
    }

    /**
     * Modify the query to the given alias can be used in the result set or queries
     *
     * This calls requireVirtualTable if needed
     *
     * @param $alias                                The alias of the column to require
     *
     * @return $this                                 Fluent interface
     * @see    IdoQuery::requireVirtualTable        The method initializing required joins
     * @throws \Icinga\Exception\ProgrammingError   When an unknown column is requested
     */
    public function requireColumn($alias)
    {
        if ($this->hasAliasName($alias)) {
            $this->requireVirtualTable($this->aliasToTableName($alias));
        } elseif ($this->isCustomVar($alias)) {
            $this->requireCustomvar($alias);
        } else {
            throw new ProgrammingError(
                '%s : Got invalid column: %s',
                get_called_class(),
                $alias
            );
        }
        return $this;
    }

    /**
     * Return true if the given alias exists
     *
     * @param  String $alias    The alias to test for
     * @return bool             True when the alias exists, otherwise false
     */
    protected function hasAliasName($alias)
    {
        return array_key_exists($alias, $this->idxAliasColumn);
    }

    /**
     * Require a virtual table for the given table name if not already required
     *
     * @param  String $name         The table name to require
     * @return $this                 Fluent interface
     */
    protected function requireVirtualTable($name)
    {
        if ($this->hasJoinedVirtualTable($name)) {
            return $this;
        }
        return $this->joinVirtualTable($name);
    }

    protected function conflictsWithVirtualTable($name)
    {
        if ($this->hasJoinedVirtualTable($name)) {
            throw new ProgrammingError(
                'IDO query virtual table conflict with "%s"',
                $name
            );
        }
        return $this;
    }

    /**
     * Call the method for joining a virtual table
     *
     * This requires a join$Table() method to exist
     *
     * @param  String $table        The table to join by calling join$Table() in the concrete implementation
     * @return $this                 Fluent interface
     *
     * @throws \Icinga\Exception\ProgrammingError   If the join method for this table does not exist
     */
    protected function joinVirtualTable($table)
    {
        $func = 'join' . ucfirst($table);
        if (method_exists($this, $func)) {
            $this->$func();
        } else {
            throw new ProgrammingError(
                'Cannot join "%s", no such table found',
                $table
            );
        }
        $this->joinedVirtualTables[$table] = true;
        return $this;
    }

    /**
     * Get the table for a specific alias
     *
     * @param   String $alias   The alias to request the table for
     * @return  String          The table for the alias or null if it doesn't exist
     */
    protected function aliasToTableName($alias)
    {
        return isset($this->idxAliasTable[$alias]) ? $this->idxAliasTable[$alias] : null;
    }

    /**
     * Return true if the given alias denotes a custom variable
     *
     * @param  String $alias    The alias to test for being a customvariable
     * @return bool             True if the alias is a customvariable, otherwise false
     */
    protected function isCustomVar($alias)
    {
        return $this->allowCustomVars && $alias[0] === '_';
    }

    protected function requireCustomvar($customvar)
    {
        if (! $this->hasCustomvar($customvar)) {
            $this->joinCustomvar($customvar);
        }
        return $this;
    }

    protected function hasCustomvar($customvar)
    {
        return array_key_exists($customvar, $this->customVars);
    }

    protected function joinCustomvar($customvar)
    {
        // TODO: This is not generic enough yet
        list($type, $name) = $this->customvarNameToTypeName($customvar);
        $alias = ($type === 'host' ? 'hcv_' : 'scv_') . strtolower($name);

        $this->customVars[$customvar] = $alias;

        // TODO: extend if we allow queries with only hosts / only services
        //       ($leftcol s.host_object_id vs h.host_object_id
        if ($this->hasJoinedVirtualTable('services')) {
            $leftcol = 's.' . $type . '_object_id';
        } else {
            $leftcol = 'h.' . $type . '_object_id';
        }
        $joinOn = sprintf(
            '%s = %s.object_id AND %s.varname = %s',
            $leftcol,
            $alias,
            $alias,
            $this->db->quote(strtoupper($name))
        );

        $this->select->joinLeft(
            array($alias => $this->prefix . 'customvariablestatus'),
            $joinOn,
            array()
        );

        return $this;
    }

    protected function customvarNameToTypeName($customvar)
    {
        // TODO: Improve this:
        if (! preg_match('~^_(host|service)_([a-zA-Z0-9_]+)$~', $customvar, $m)) {
            throw new ProgrammingError(
                'Got invalid custom var: "%s"',
                $customvar
            );
        }
        return array($m[1], $m[2]);
    }

    protected function hasJoinedVirtualTable($name)
    {
        return array_key_exists($name, $this->joinedVirtualTables);
    }

    protected function getCustomvarColumnName($customvar)
    {
        return $this->customVars[$customvar] . '.varvalue';
    }

    public function aliasToColumnName($alias)
    {
        return $this->idxAliasColumn[$alias];
    }

    protected function createSubQuery($queryName, $columns = array())
    {
        $class = '\\'
            . substr(__CLASS__, 0, strrpos(__CLASS__, '\\') + 1)
            . ucfirst($queryName) . 'Query';
        $query = new $class($this->ds, $columns);
        return $query;
    }

    /**
     * Set columns to select
     *
     * @param   array $columns
     *
     * @return  $this
     */
    public function columns(array $columns)
    {
        $this->columns = $this->resolveColumns($columns);
        // TODO: we need to refresh our select!
        // $this->select->columns($columns);
        return $this;
    }

    // TODO: Move this away, see note related to $idoVersion var
    protected function getIdoVersion()
    {
        if (self::$idoVersion === null) {
            $dbconf = $this->db->getConfig();
            $id = $dbconf['host'] . '/' . $dbconf['dbname'];
            $session = null;
            if (Icinga::app()->isWeb()) {
                // TODO: Once we have version per connection we should choose a
                //       namespace based on resource name
                $session = Session::getSession()->getNamespace('monitoring/ido/' . $id);
                if (isset($session->version)) {
                    self::$idoVersion = $session->version;
                    return self::$idoVersion;
                }
            }
            self::$idoVersion = $this->db->fetchOne(
                $this->db->select()->from($this->prefix . 'dbversion', 'version')
            );
            if ($session !== null) {
                $session->version = self::$idoVersion;
            }
        }
        return self::$idoVersion;
    }
}
