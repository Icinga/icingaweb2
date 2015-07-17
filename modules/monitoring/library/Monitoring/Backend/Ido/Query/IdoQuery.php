<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Data\Db\DbQuery;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotImplementedError;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Session;

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
     * @var string
     */
    protected $prefix;

    /**
     * An array to map aliases to table names
     *
     * @var array
     */
    protected $idxAliasColumn;

    /**
     * An array to map aliases to column names
     *
     * @var array
     */
    protected $idxAliasTable;

    /**
     * An array to map custom aliases to aliases
     *
     * @var array
     */
    protected $idxCustomAliases;

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
     * Printf compatible string to joins custom vars
     *
     * - %1$s   Source field, contain the object_id
     * - %2$s   Alias used for the relation
     * - %3$s   Name of the CustomVariable
     *
     * @var string
     */
    private $customVarsJoinTemplate =
        '%1$s = %2$s.object_id AND %2$s.varname = %3$s COLLATE latin1_general_ci';

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
     * The primary key column for the instances table
     *
     * @var string
     */
    protected $instance_id = 'instance_id';

    /**
     * The primary key column for the objects table
     *
     * @var string
     */
    protected $object_id       = 'object_id';

    /**
     * The primary key column for the acknowledgements table
     *
     * @var string
     */
    protected $acknowledgement_id = 'acknowledgement_id';

    /**
     * The primary key column for the commenthistory table
     *
     * @var string
     */
    protected $commenthistory_id = 'commenthistory_id';

    /**
     * The primary key column for the contactnotifications table
     *
     * @var string
     */
    protected $contactnotification_id = 'contactnotification_id';

    /**
     * The primary key column for the downtimehistory table
     *
     * @var string
     */
    protected $downtimehistory_id = 'downtimehistory_id';

    /**
     * The primary key column for the flappinghistory table
     *
     * @var string
     */
    protected $flappinghistory_id = 'flappinghistory_id';

    /**
     * The primary key column for the notifications table
     *
     * @var string
     */
    protected $notification_id = 'notification_id';

    /**
     * The primary key column for the statehistory table
     *
     * @var string
     */
    protected $statehistory_id = 'statehistory_id';

    /**
     * The primary key column for the comments table
     *
     * @var string
     */
    protected $comment_id = 'comment_id';

    /**
     * The primary key column for the customvariablestatus table
     *
     * @var string
     */
    protected $customvariablestatus_id = 'customvariablestatus_id';

    /**
     * The primary key column for the hoststatus table
     *
     * @var string
     */
    protected $hoststatus_id = 'hoststatus_id';

    /**
     * The primary key column for the programstatus table
     *
     * @var string
     */
    protected $programstatus_id = 'programstatus_id';

    /**
     * The primary key column for the runtimevariables table
     *
     * @var string
     */
    protected $runtimevariable_id = 'runtimevariable_id';

    /**
     * The primary key column for the scheduleddowntime table
     *
     * @var string
     */
    protected $scheduleddowntime_id = 'scheduleddowntime_id';

    /**
     * The primary key column for the servicestatus table
     *
     * @var string
     */
    protected $servicestatus_id = 'servicestatus_id';

    /**
     * The primary key column for the contactstatus table
     *
     * @var string
     */
    protected $contactstatus_id = 'contactstatus_id';

    /**
     * The primary key column for the commands table
     *
     * @var string
     */
    protected $command_id = 'command_id';

    /**
     * The primary key column for the contactgroup_members table
     *
     * @var string
     */
    protected $contactgroup_member_id = 'contactgroup_member_id';

    /**
     * The primary key column for the contactgroups table
     *
     * @var string
     */
    protected $contactgroup_id = 'contactgroup_id';

    /**
     * The primary key column for the contacts table
     *
     * @var string
     */
    protected $contact_id = 'contact_id';

    /**
     * The primary key column for the customvariables table
     *
     * @var string
     */
    protected $customvariable_id = 'customvariable_id';

    /**
     * The primary key column for the host_contactgroups table
     *
     * @var string
     */
    protected $host_contactgroup_id = 'host_contactgroup_id';

    /**
     * The primary key column for the host_contacts table
     *
     * @var string
     */
    protected $host_contact_id = 'host_contact_id';

    /**
     * The primary key column for the hostgroup_members table
     *
     * @var string
     */
    protected $hostgroup_member_id = 'hostgroup_member_id';

    /**
     * The primary key column for the hostgroups table
     *
     * @var string
     */
    protected $hostgroup_id = 'hostgroup_id';

    /**
     * The primary key column for the hosts table
     *
     * @var string
     */
    protected $host_id = 'host_id';

    /**
     * The primary key column for the service_contactgroup table
     *
     * @var string
     */
    protected $service_contactgroup_id = 'service_contactgroup_id';

    /**
     * The primary key column for the service_contact table
     *
     * @var string
     */
    protected $service_contact_id = 'service_contact_id';

    /**
     * The primary key column for the servicegroup_members table
     *
     * @var string
     */
    protected $servicegroup_member_id = 'servicegroup_member_id';

    /**
     * The primary key column for the servicegroups table
     *
     * @var string
     */
    protected $servicegroup_id = 'servicegroup_id';

    /**
     * The primary key column for the services table
     *
     * @var string
     */
    protected $service_id = 'service_id';

    /**
     * The primary key column for the timeperiods table
     *
     * @var string
     */
    protected $timeperiod_id = 'timeperiod_id';

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
            if ($filter->getExpression() === '*') {
                return; // Wildcard only filters are ignored so stop early here to avoid joining a table for nothing
            }

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
        return parent::addFilter($filter);
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
        if ($value === '*') {
            return $this; // Wildcard only filters are ignored so stop early here to avoid joining a table for nothing
        }

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
        $this->customVarsJoinTemplate =
            '%1$s = %2$s.object_id AND LOWER(%2$s.varname) = %3$s';
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
        $this->customVarsJoinTemplate =
            '%1$s = %2$s.object_id AND LOWER(%2$s.varname) = %3$s';
        foreach ($this->columnMap as $table => & $columns) {
            foreach ($columns as $key => & $value) {
                $value = preg_replace('/ COLLATE .+$/', '', $value, -1, $count);
                if ($count > 0) {
                    $this->columnsWithoutCollation[] = $this->getMappedField($key);
                }
                $value = preg_replace(
                    '/inet_aton\(([[:word:].]+)\)/i',
                    '(CASE WHEN $1 ~ \'(?:[0-9]{1,3}\\\\.){3}[0-9]{1,3}\' THEN $1::inet - \'0.0.0.0\' ELSE NULL END)',
                    $value
                );
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
        $dbType = $this->ds->getDbType();
        if ($dbType === 'oracle') {
            $this->initializeForOracle();
        } elseif ($dbType === 'pgsql') {
            $this->initializeForPostgres();
        }
        $this->joinBaseTables();
        $this->select->columns($this->columns);
        $this->prepareAliasIndexes();
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
            if ($col instanceof Zend_Db_Expr) {
                // Support selecting NULL as column for example
                $resolvedColumns[$alias] = $col;
                continue;
            }
            $this->requireColumn($col);
            if ($this->isCustomvar($col)) {
                $name = $this->getCustomvarColumnName($col);
            } else {
                $name = $this->aliasToColumnName($col);
            }
            if (is_int($alias)) {
                $alias = $col;
            } else {
                $this->idxCustomAliases[$alias] = $col;
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
        return array_key_exists(strtolower($customvar), $this->customVars);
    }

    protected function joinCustomvar($customvar)
    {
        // TODO: This is not generic enough yet
        list($type, $name) = $this->customvarNameToTypeName($customvar);
        $alias = ($type === 'host' ? 'hcv_' : 'scv_') . $name;

        $this->customVars[$customvar] = $alias;

        if ($this->hasJoinedVirtualTable('services')) {
            $leftcol = 's.' . $type . '_object_id';
        } elseif ($type === 'service') {
            $this->requireVirtualTable('services');
            $leftcol = 's.service_object_id';
        } else {
            $this->requireVirtualTable('hosts');
            $leftcol = 'h.host_object_id';
        }

        $joinOn = sprintf(
            $this->customVarsJoinTemplate,
            $leftcol,
            $alias,
            $this->db->quote($name)
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
        $customvar = strtolower($customvar);
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
        if (isset($this->customVars[($customvar = strtolower($customvar))])) {
            $this->customVars[strtolower($customvar)] . '.varvalue';
        }
    }

    public function aliasToColumnName($alias)
    {
        return $this->idxAliasColumn[$alias];
    }

    public function customAliasToAlias($alias)
    {
        return $this->idxCustomAliases[$alias];
    }

    /**
     * Create a sub query
     *
     * @param   string  $queryName
     * @param   array   $columns
     *
     * @return  static
     */
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
        $this->idxCustomAliases = array();
        $this->columns = $this->resolveColumns($columns);
        // TODO: we need to refresh our select!
        // $this->select->columns($columns);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function _getGroup()
    {
        throw new NotImplementedError('Does not work in its current state but will, probably, in the future');

        // TODO: order by??
        $group = parent::getGroup();
        if (! empty($group) && $this->ds->getDbType() === 'pgsql') {
            $group = is_array($group) ? $group : array($group);
            foreach ($this->columns as $alias => $column) {
                if ($column instanceof Zend_Db_Expr) {
                    continue;
                }

                // TODO: What if $alias is neither a native nor a custom alias???
                $table = $this->aliasToTableName(
                    $this->hasAliasName($alias) ? $alias : $this->customAliasToAlias($alias)
                );

                // TODO: We cannot rely on the underlying select here, tables may be joined multiple times with
                //       different aliases so the only way to get the correct alias here is to register such by ourself
                //       for each virtual column (We may also inspect $column for the alias but this will probably lead
                //       to false positives.. AND prevents custom implementations from providing their own "mapping")
                if (($tableAlias = $this->getJoinedTableAlias($this->prefix . $table)) === null) {
                    $tableAlias = $table;
                }

                // TODO: Same issue as with identifying table aliases; Our virtual tables are not named exactly how
                //       they are in the IDO. We definitely need to register aliases explicitly (hint: DbRepository
                //       is already providing such..)
                $aliasedPk = $tableAlias . '.' . $this->getPrimaryKeyColumn($table);
                if (! in_array($aliasedPk, $group)) {
                    $group[] = $aliasedPk;
                }
            }
        }

        return $group;
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

    /**
     * Return the name of the primary key column for the given table name
     *
     * @param   string  $table
     *
     * @return  string
     *
     * @throws ProgrammingError     In case $table is unknown
     */
    protected function getPrimaryKeyColumn($table)
    {
        // TODO: For god's sake, make this being a mapping
        //       (instead of matching a ton of properties using a ridiculous long switch case)
        switch ($table)
        {
            case 'instances':
                return $this->instance_id;
            case 'objects':
                return $this->object_id;
            case 'acknowledgements':
                return $this->acknowledgement_id;
            case 'commenthistory':
                return $this->commenthistory_id;
            case 'contactnotifiations':
                return $this->contactnotification_id;
            case 'downtimehistory':
                return $this->downtimehistory_id;
            case 'flappinghistory':
                return $this->flappinghistory_id;
            case 'notifications':
                return $this->notification_id;
            case 'statehistory':
                return $this->statehistory_id;
            case 'comments':
                return $this->comment_id;
            case 'customvariablestatus':
                return $this->customvariablestatus_id;
            case 'hoststatus':
                return $this->hoststatus_id;
            case 'programstatus':
                return $this->programstatus_id;
            case 'runtimevariables':
                return $this->runtimevariable_id;
            case 'scheduleddowntime':
                return $this->scheduleddowntime_id;
            case 'servicestatus':
                return $this->servicestatus_id;
            case 'contactstatus':
                return $this->contactstatus_id;
            case 'commands':
                return $this->command_id;
            case 'contactgroup_members':
                return $this->contactgroup_member_id;
            case 'contactgroups':
                return $this->contactgroup_id;
            case 'contacts':
                return $this->contact_id;
            case 'customvariables':
                return $this->customvariable_id;
            case 'host_contactgroups':
                return $this->host_contactgroup_id;
            case 'host_contacts':
                return $this->host_contact_id;
            case 'hostgroup_members':
                return $this->hostgroup_member_id;
            case 'hostgroups':
                return $this->hostgroup_id;
            case 'hosts':
                return $this->host_id;
            case 'service_contactgroups':
                return $this->service_contactgroup_id;
            case 'service_contacts':
                return $this->service_contact_id;
            case 'servicegroup_members':
                return $this->servicegroup_member_id;
            case 'servicegroups':
                return $this->servicegroup_id;
            case 'services':
                return $this->service_id;
            case 'timeperiods':
                return $this->timeperiod_id;
            default:
                throw new ProgrammingError('Cannot provide a primary key column. Table "%s" is unknown', $table);
        }
    }
}
