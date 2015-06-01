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
use Icinga\Util\String;

/**
 * Abstract base class for concrete database repository implementations
 *
 * Additionally provided features:
 * <ul>
 *  <li>Support for table aliases</li>
 *  <li>Automatic table prefix handling</li>
 *  <li>Insert, update and delete capabilities</li>
 *  <li>Differentiation between statement and query columns</li>
 *  <li>Capability to join additional tables depending on the columns being selected or used in a filter</li>
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
     * The table aliases being applied
     *
     * This must be initialized by repositories which are going to make use of table aliases. Every table for which
     * aliased columns are provided must be defined in this array using its name as key and the alias being used as
     * value. Failure to do so will result in invalid queries.
     *
     * @var array
     */
    protected $tableAliases;

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
     * List of columns where the COLLATE SQL-instruction has been removed
     *
     * This list is being populated in case of a PostgreSQL backend only,
     * to ensure case-insensitive string comparison in WHERE clauses.
     *
     * @var array
     */
    protected $columnsWithoutCollation;

    /**
     * Create a new DB repository object
     *
     * In case $this->queryColumns has already been initialized, this initializes
     * $this->columnsWithoutCollation in case of a PostgreSQL connection.
     *
     * @param   DbConnection    $ds     The datasource to use
     */
    public function __construct(DbConnection $ds)
    {
        parent::__construct($ds);

        $this->columnsWithoutCollation = array();
        if ($ds->getDbType() === 'pgsql' && $this->queryColumns !== null) {
            $this->queryColumns = $this->removeCollateInstruction($this->queryColumns);
        }
    }

    /**
     * Return the query columns being provided
     *
     * Initializes $this->columnsWithoutCollation in case of a PostgreSQL connection.
     *
     * @return  array
     */
    public function getQueryColumns()
    {
        if ($this->queryColumns === null) {
            $this->queryColumns = parent::getQueryColumns();
            if ($this->ds->getDbType() === 'pgsql') {
                $this->queryColumns = $this->removeCollateInstruction($this->queryColumns);
            }
        }

        return $this->queryColumns;
    }

    /**
     * Return the table aliases to be applied
     *
     * Calls $this->initializeTableAliases() in case $this->tableAliases is null.
     *
     * @return  array
     */
    public function getTableAliases()
    {
        if ($this->tableAliases === null) {
            $this->tableAliases = $this->initializeTableAliases();
        }

        return $this->tableAliases;
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize the table aliases lazily
     *
     * @return  array
     */
    protected function initializeTableAliases()
    {
        return array();
    }

    /**
     * Remove each COLLATE SQL-instruction from all given query columns
     *
     * @param   array   $queryColumns
     *
     * @return  array                   $queryColumns, the updated version
     */
    protected function removeCollateInstruction($queryColumns)
    {
        foreach ($queryColumns as & $columns) {
            foreach ($columns as & $column) {
                $column = preg_replace('/ COLLATE .+$/', '', $column, -1, $count);
                if ($count > 0) {
                    $this->columnsWithoutCollation[] = $column;
                }
            }
        }

        return $queryColumns;
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
     * @param   array|string    $table
     *
     * @return  array|string
     *
     * @throws  IcingaException         In case $table is not of a supported type
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
     * Return the given table with its alias being applied
     *
     * @param   array|string    $table
     *
     * @return  array|string
     */
    protected function applyTableAlias($table)
    {
        $tableAliases = $this->getTableAliases();
        if (is_array($table) || !isset($tableAliases[($nonPrefixedTable = $this->removeTablePrefix($table))])) {
            return $table;
        }

        return array($tableAliases[$nonPrefixedTable] => $table);
    }

    /**
     * Return the given table with its alias being cleared
     *
     * @param   array|string    $table
     *
     * @return  string
     *
     * @throws  IcingaException         In case $table is not of a supported type
     */
    protected function clearTableAlias($table)
    {
        if (is_string($table)) {
            return $table;
        }

        if (is_array($table)) {
            return reset($table);
        }

        throw new IcingaException('Table alias handling for type "%s" is not supported', type($table));
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
            $filter = $this->requireFilter($table, $filter);
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
            $filter = $this->requireFilter($table, $filter);
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
     * Return whether this repository is capable of converting values
     *
     * This does not check whether any conversion for the given table is available, as it may be possible
     * that columns from another table where joined in which would otherwise not being converted.
     *
     * @param   array|string    $table
     *
     * @return  bool
     */
    public function providesValueConversion($_)
    {
        $conversionRules = $this->getConversionRules();
        return !empty($conversionRules);
    }

    /**
     * Return the name of the conversion method for the given alias or column name and context
     *
     * @param   array|string    $table      The datasource's table
     * @param   string          $name       The alias or column name for which to return a conversion method
     * @param   string          $context    The context of the conversion: persist or retrieve
     *
     * @return  string
     *
     * @throws  ProgrammingError    In case a conversion rule is found but not any conversion method
     */
    protected function getConverter($table, $name, $context)
    {
        if (
            $this->validateQueryColumnAssociation($table, $name)
            || $this->validateStatementColumnAssociation($table, $name)
        ) {
            $table = $this->removeTablePrefix($this->clearTableAlias($table));
        } else {
            $table = $this->findTableName($name);
            if (! $table) {
                throw new ProgrammingError('Column name validation seems to have failed. Did you require the column?');
            }
        }

        return parent::getConverter($table, $name, $context);
    }

    /**
     * Validate that the requested table exists
     *
     * This will prepend the datasource's table prefix and will apply the table's alias, if any.
     *
     * @param   string              $table      The table to validate
     * @param   RepositoryQuery     $query      An optional query to pass as context
     *                                          (unused by the base implementation)
     *
     * @return  array|string
     *
     * @throws  ProgrammingError                In case the given table does not exist
     */
    public function requireTable($table, RepositoryQuery $query = null)
    {
        $statementColumns = $this->getStatementColumns();
        if (! isset($statementColumns[$table])) {
            $table = parent::requireTable($table);
        }

        return $this->prependTablePrefix($this->applyTableAlias($table));
    }

    /**
     * Recurse the given filter, require each column for the given table and convert all values
     *
     * In case of a PostgreSQL connection, this applies LOWER() on the column and strtolower()
     * on the value if a COLLATE SQL-instruction is part of the resolved column.
     *
     * @param   string              $table      The table being filtered
     * @param   Filter              $filter     The filter to recurse
     * @param   RepositoryQuery     $query      An optional query to pass as context
     *                                          (Directly passed through to $this->requireFilterColumn)
     * @param   bool                $clone      Whether to clone $filter first
     *
     * @return  Filter                          The udpated filter
     */
    public function requireFilter($table, Filter $filter, RepositoryQuery $query = null, $clone = true)
    {
        $filter = parent::requireFilter($table, $filter, $query, $clone);

        if ($filter->isExpression()) {
            $column = $filter->getColumn();
            if (in_array($column, $this->columnsWithoutCollation) && strpos($column, 'LOWER') !== 0) {
                $filter->setColumn('LOWER(' . $column . ')');
                $expression = $filter->getExpression();
                if (is_array($expression)) {
                    $filter->setExpression(array_map('strtolower', $expression));
                } else {
                    $filter->setExpression(strtolower($expression));
                }
            }
        }

        return $filter;
    }

    /**
     * Return this repository's query columns of the given table mapped to their respective aliases
     *
     * @param   array|string    $table
     *
     * @return  array
     *
     * @throws  ProgrammingError    In case $table does not exist
     */
    public function requireAllQueryColumns($table)
    {
        return parent::requireAllQueryColumns($this->removeTablePrefix($this->clearTableAlias($table)));
    }

    /**
     * Return the query column name for the given alias or null in case the alias does not exist
     *
     * @param   array|string    $table
     * @param   string          $alias
     *
     * @return  string|null
     */
    public function resolveQueryColumnAlias($table, $alias)
    {
        return parent::resolveQueryColumnAlias($this->removeTablePrefix($this->clearTableAlias($table)), $alias);
    }

    /**
     * Return whether the given query column name or alias is available in the given table
     *
     * @param   array|string    $table
     * @param   string          $column
     *
     * @return  bool
     */
    public function validateQueryColumnAssociation($table, $column)
    {
        return parent::validateQueryColumnAssociation(
            $this->removeTablePrefix($this->clearTableAlias($table)),
            $column
        );
    }

    /**
     * Validate that the given column is a valid query target and return it or the actual name if it's an alias
     *
     * Attempts to join the given column from a different table if its association to the given table cannot be
     * verified.
     *
     * @param   string              $table  The table where to look for the column or alias
     * @param   string              $name   The name or alias of the column to validate
     * @param   RepositoryQuery     $query  An optional query to pass as context,
     *                                      if not given no join will be attempted
     *
     * @return  string                      The given column's name
     *
     * @throws  QueryException              In case the given column is not a valid query column
     */
    public function requireQueryColumn($table, $name, RepositoryQuery $query = null)
    {
        if ($query === null || $this->validateQueryColumnAssociation($table, $name)) {
            return parent::requireQueryColumn($table, $name, $query);
        }

        return $this->joinColumn($name, $table, $query);
    }

    /**
     * Validate that the given column is a valid filter target and return it or the actual name if it's an alias
     *
     * Attempts to join the given column from a different table if its association to the given table cannot be
     * verified.
     *
     * @param   string              $table  The table where to look for the column or alias
     * @param   string              $name   The name or alias of the column to validate
     * @param   RepositoryQuery     $query  An optional query to pass as context,
     *                                      if not given the column is considered being used for a statement filter
     *
     * @return  string                      The given column's name
     *
     * @throws  QueryException              In case the given column is not a valid filter column
     */
    public function requireFilterColumn($table, $name, RepositoryQuery $query = null)
    {
        if ($query === null) {
            return $this->requireStatementColumn($table, $name);
        }

        if ($this->validateQueryColumnAssociation($table, $name)) {
            return parent::requireFilterColumn($table, $name, $query);
        }

        return $this->joinColumn($name, $table, $query);
    }

    /**
     * Return the statement column name for the given alias or null in case the alias does not exist
     *
     * @param   string  $table
     * @param   string  $alias
     *
     * @return  string|null
     */
    public function resolveStatementColumnAlias($table, $alias)
    {
        $statementColumnMap = $this->getStatementColumnMap();
        if (isset($statementColumnMap[$alias])) {
            return $statementColumnMap[$alias];
        }

        $prefixedAlias = $this->removeTablePrefix($table) . '.' . $alias;
        if (isset($statementColumnMap[$prefixedAlias])) {
            return $statementColumnMap[$prefixedAlias];
        }
    }

    /**
     * Return whether the given alias or statement column name is available in the given table
     *
     * @param   string  $table
     * @param   string  $alias
     *
     * @return  bool
     */
    public function validateStatementColumnAssociation($table, $alias)
    {
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
     * @param   string  $table  The table where to look for the column or alias
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
     * @param   string  $table      The table for which to require the column
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

    /**
     * Join alias or column $name into $table using $query
     *
     * Attempts to find a valid table for the given alias or column name and a method labelled join<TableName>
     * to process the actual join logic. If neither of those is found, ProgrammingError will be thrown.
     * The method is called with the same parameters but in reversed order.
     *
     * @param   string              $name       The alias or column name to join into $target
     * @param   array|string        $target     The table to join $name into
     * @param   RepositoryQUery     $query      The query to apply the JOIN-clause on
     *
     * @return  string                          The resolved alias or $name
     *
     * @throws  ProgrammingError                In case no valid table or join<TableName>-method is found
     */
    public function joinColumn($name, $target, RepositoryQuery $query)
    {
        $tableName = $this->findTableName($name);
        if (! $tableName) {
            throw new ProgrammingError(
                'Unable to find a valid table for column "%s" to join into "%s"',
                $name,
                $this->removeTablePrefix($this->clearTableAlias($target))
            );
        }

        $column = $this->resolveQueryColumnAlias($tableName, $name);

        $prefixedTableName = $this->prependTablePrefix($tableName);
        if ($query->getQuery()->hasJoinedTable($prefixedTableName)) {
            return $column;
        }

        $joinMethod = 'join' . String::cname($tableName);
        if (! method_exists($this, $joinMethod)) {
            throw new ProgrammingError(
                'Unable to join table "%s" into "%s". Method "%s" not found',
                $tableName,
                $this->removeTablePrefix($this->clearTableAlias($target)),
                $joinMethod
            );
        }

        $this->$joinMethod($query, $target, $name);
        return $column;
    }

    /**
     * Return the table name for the given alias or column name
     *
     * @param   string  $column
     *
     * @return  string|null         null in case no table is found
     */
    protected function findTableName($column)
    {
        $aliasTableMap = $this->getAliasTableMap();
        if (isset($aliasTableMap[$column])) {
            return $aliasTableMap[$column];
        }

        // TODO(jom): Elaborate whether it makes sense to throw ProgrammingError
        //            instead (duplicate aliases in different tables?)
        foreach ($aliasTableMap as $alias => $table) {
            if (strpos($alias, '.') !== false) {
                list($_, $alias) = explode('.', $column, 2);
                if ($alias === $column) {
                    return $table;
                }
            }
        }
    }
}
