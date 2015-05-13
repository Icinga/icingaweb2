<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Repository;

use Icinga\Data\Db\DbConnection;
use Icinga\Data\Extensible;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Reducible;
use Icinga\Data\Updatable;
use Icinga\Exception\IcingaException;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\StatementException;

/**
 * Abstract base class for concrete database repository implementations
 *
 * Additionally provided features:
 * <ul>
 *  <li>Automatic table prefix handling</li>
 *  <li>Insert, update and delete capabilities</li>
 *  <li>Differentiation between statement and query columns</li>
 * </ul>
 */
abstract class DbRepository extends Repository implements Extensible, Updatable, Reducible
{
    /**
     * The datasource being used
     *
     * @var DbConnection
     */
    protected $ds;

    /**
     * The statement columns being provided
     *
     * This may be initialized by repositories which are going to make use of table aliases. It allows to provide
     * alias-less column names to be used for a statement. The array needs to be in the following format:
     * <pre><code>
     *  array(
     *      'table_name' => array(
     *          'column1',
     *          'alias1' => 'column2',
     *          'alias2' => 'column3'
     *      )
     *  )
     * <pre><code>
     *
     * @var array
     */
    protected $statementColumns;

    /**
     * An array to map table names to statement columns/aliases
     *
     * @var array
     */
    protected $statementTableMap;

    /**
     * A flattened array to map statement columns to aliases
     *
     * @var array
     */
    protected $statementColumnMap;

    /**
     * Create a new DB repository object
     *
     * @param   DbConnection    $ds     The datasource to use
     */
    public function __construct(DbConnection $ds)
    {
        parent::__construct($ds);
    }

    /**
     * Return the base table name this repository is responsible for
     *
     * This prepends the datasource's table prefix, if available and required.
     *
     * @return  mixed
     *
     * @throws  ProgrammingError    In case no base table name has been set and
     *                               $this->queryColumns does not provide one either
     */
    public function getBaseTable()
    {
        return $this->prependTablePrefix(parent::getBaseTable());
    }

    /**
     * Return the given table with the datasource's prefix being prepended
     *
     * @param   array|string    $table
     *
     * @return  array|string
     *
     * @throws  IcingaException         In case $table is not of a supported type
     */
    protected function prependTablePrefix($table)
    {
        $prefix = $this->ds->getTablePrefix();
        if (! $prefix) {
            return $table;
        }

        if (is_array($table)) {
            foreach ($table as & $tableName) {
                if (strpos($tableName, $prefix) === false) {
                    $tableName = $prefix . $tableName;
                }
            }
        } elseif (is_string($table)) {
            $table = (strpos($table, $prefix) === false ? $prefix : '') . $table;
        } else {
            throw new IcingaException('Table prefix handling for type "%s" is not supported', type($table));
        }

        return $table;
    }

    /**
     * Remove the datasource's prefix from the given table name and return the remaining part
     *
     * @param   mixed   $table
     *
     * @return  mixed
     */
    protected function removeTablePrefix($table)
    {
        $prefix = $this->ds->getTablePrefix();
        if (! $prefix) {
            return $table;
        }

        if (is_array($table)) {
            foreach ($table as & $tableName) {
                if (strpos($tableName, $prefix) === 0) {
                    $tableName = str_replace($prefix, '', $tableName);
                }
            }
        } elseif (is_string($table)) {
            if (strpos($table, $prefix) === 0) {
                $table = str_replace($prefix, '', $table);
            }
        } else {
            throw new IcingaException('Table prefix handling for type "%s" is not supported', type($table));
        }

        return $table;
    }

    /**
     * Insert a table row with the given data
     *
     * @param   string  $table
     * @param   array   $bind
     */
    public function insert($table, array $bind)
    {
        $this->ds->insert($this->prependTablePrefix($table), $this->requireStatementColumns($table, $bind));
    }

    /**
     * Update table rows with the given data, optionally limited by using a filter
     *
     * @param   string  $table
     * @param   array   $bind
     * @param   Filter  $filter
     */
    public function update($table, array $bind, Filter $filter = null)
    {
        if ($filter) {
            $this->requireFilter($table, $filter);
        }

        $this->ds->update($this->prependTablePrefix($table), $this->requireStatementColumns($table, $bind), $filter);
    }

    /**
     * Delete table rows, optionally limited by using a filter
     *
     * @param   string  $table
     * @param   Filter  $filter
     */
    public function delete($table, Filter $filter = null)
    {
        if ($filter) {
            $this->requireFilter($table, $filter);
        }

        $this->ds->delete($this->prependTablePrefix($table), $filter);
    }

    /**
     * Return the statement columns being provided
     *
     * Calls $this->initializeStatementColumns() in case $this->statementColumns is null.
     *
     * @return  array
     */
    public function getStatementColumns()
    {
        if ($this->statementColumns === null) {
            $this->statementColumns = $this->initializeStatementColumns();
        }

        return $this->statementColumns;
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize the statement columns lazily
     *
     * @return  array
     */
    protected function initializeStatementColumns()
    {
        return array();
    }

    /**
     * Return an array to map table names to statement columns/aliases
     *
     * @return  array
     */
    protected function getStatementTableMap()
    {
        if ($this->statementTableMap === null) {
            $this->initializeStatementMaps();
        }

        return $this->statementTableMap;
    }

    /**
     * Return a flattened array to map statement columns to aliases
     *
     * @return  array
     */
    protected function getStatementColumnMap()
    {
        if ($this->statementColumnMap === null) {
            $this->initializeStatementMaps();
        }

        return $this->statementColumnMap;
    }

    /**
     * Initialize $this->statementTableMap and $this->statementColumnMap
     */
    protected function initializeStatementMaps()
    {
        $this->statementTableMap = array();
        $this->statementColumnMap = array();
        foreach ($this->getStatementColumns() as $table => $columns) {
            foreach ($columns as $alias => $column) {
                $key = is_string($alias) ? $alias : $column;
                if (array_key_exists($key, $this->statementTableMap)) {
                    if ($this->statementTableMap[$key] !== null) {
                        $existingTable = $this->statementTableMap[$key];
                        $existingColumn = $this->statementColumnMap[$key];
                        $this->statementTableMap[$existingTable . '.' . $key] = $existingTable;
                        $this->statementColumnMap[$existingTable . '.' . $key] = $existingColumn;
                        $this->statementTableMap[$key] = null;
                        $this->statementColumnMap[$key] = null;
                    }

                    $this->statementTableMap[$table . '.' . $key] = $table;
                    $this->statementColumnMap[$table . '.' . $key] = $column;
                } else {
                    $this->statementTableMap[$key] = $table;
                    $this->statementColumnMap[$key] = $column;
                }
            }
        }
    }

    /**
     * Return this repository's query columns of the given table mapped to their respective aliases
     *
     * @param   mixed   $table
     *
     * @return  array
     *
     * @throws  ProgrammingError    In case $table does not exist
     */
    public function requireAllQueryColumns($table)
    {
        if (is_array($table)) {
            $table = array_shift($table);
        }

        return parent::requireAllQueryColumns($this->removeTablePrefix($table));
    }

    /**
     * Return the query column name for the given alias or null in case the alias does not exist
     *
     * @param   mixed   $table
     * @param   string  $alias
     *
     * @return  string|null
     */
    public function resolveQueryColumnAlias($table, $alias)
    {
        if (is_array($table)) {
            $table = array_shift($table);
        }

        return parent::resolveQueryColumnAlias($this->removeTablePrefix($table), $alias);
    }

    /**
     * Return whether the given query column name or alias is available in the given table
     *
     * @param   mixed   $table
     * @param   string  $column
     *
     * @return  bool
     */
    public function validateQueryColumnAssociation($table, $column)
    {
        if (is_array($table)) {
            $table = array_shift($table);
        }

        return parent::validateQueryColumnAssociation($this->removeTablePrefix($table), $column);
    }

    /**
     * Return the statement column name for the given alias or null in case the alias does not exist
     *
     * @param   mixed   $table
     * @param   string  $alias
     *
     * @return  string|null
     */
    public function resolveStatementColumnAlias($table, $alias)
    {
        if (is_array($table)) {
            $table = array_shift($table);
        }

        $statementColumnMap = $this->getStatementColumnMap();
        if (isset($statementColumnMap[$alias])) {
            return $statementColumnMap[$alias];
        }

        $prefixedAlias = $table . '.' . $alias;
        if (isset($statementColumnMap[$prefixedAlias])) {
            return $statementColumnMap[$prefixedAlias];
        }
    }

    /**
     * Return whether the given alias or statement column name is available in the given table
     *
     * @param   mixed   $table
     * @param   string  $alias
     *
     * @return  bool
     */
    public function validateStatementColumnAssociation($table, $alias)
    {
        if (is_array($table)) {
            $table = array_shift($table);
        }

        $statementTableMap = $this->getStatementTableMap();
        if (isset($statementTableMap[$alias])) {
            return $statementTableMap[$alias] === $this->removeTablePrefix($table);
        }

        $prefixedAlias = $this->removeTablePrefix($table) . '.' . $alias;
        return isset($statementTableMap[$prefixedAlias]);
    }

    /**
     * Return whether the given column name or alias of the given table is a valid statement column
     *
     * @param   mixed   $table  The table where to look for the column or alias
     * @param   string  $name   The column name or alias to check
     *
     * @return  bool
     */
    public function hasStatementColumn($table, $name)
    {
        if (
            $this->resolveStatementColumnAlias($table, $name) === null
            || !$this->validateStatementColumnAssociation($table, $name)
        ) {
            return parent::hasStatementColumn($table, $name);
        }

        return true;
    }

    /**
     * Validate that the given column is a valid statement column and return it or the actual name if it's an alias
     *
     * @param   mixed   $table      The table for which to require the column
     * @param   string  $name       The name or alias of the column to validate
     *
     * @return  string              The given column's name
     *
     * @throws  StatementException  In case the given column is not a statement column
     */
    public function requireStatementColumn($table, $name)
    {
        if (($column = $this->resolveStatementColumnAlias($table, $name)) === null) {
            return parent::requireStatementColumn($table, $name);
        }

        if (! $this->validateStatementColumnAssociation($table, $name)) {
            throw new StatementException('Statement column "%s" not found in table "%s"', $name, $table);
        }

        return $column;
    }
}
