<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Repository;

use Zend_Db_Expr;
use Icinga\Data\Db\DbConnection;
use Icinga\Data\Extensible;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;
use Icinga\Data\Reducible;
use Icinga\Data\Updatable;
use Icinga\Exception\IcingaException;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\StatementException;
use Icinga\Util\StringHelper;

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
 *
 * @method DbConnection getDataSource($table = null)
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
     * The join probability rules
     *
     * This may be initialized by repositories which make use of the table join capability. It allows to define
     * probability rules to enhance control how ambiguous column aliases are associated with the correct table.
     * To define a rule use the name of a base table as key and another array of table names as probable join
     * targets ordered by priority. (Ascending: Lower means higher priority)
     * <code>
     *  array(
     *      'table_name' => array('target1', 'target2', 'target3')
     *  )
     * </code>
     *
     * @todo    Support for tree-ish rules
     *
     * @var array
     */
    protected $joinProbabilities;

    /**
     * The statement columns being provided
     *
     * This may be initialized by repositories which are going to make use of table aliases. It allows to provide
     * alias-less column names to be used for a statement. The array needs to be in the following format:
     * <code>
     *  array(
     *      'table_name' => array(
     *          'column1',
     *          'alias1' => 'column2',
     *          'alias2' => 'column3'
     *      )
     *  )
     * </code>
     *
     * @var array
     */
    protected $statementColumns;

    /**
     * An array to map table names to statement columns/aliases
     *
     * @var array
     */
    protected $statementAliasTableMap;

    /**
     * A flattened array to map statement columns to aliases
     *
     * @var array
     */
    protected $statementAliasColumnMap;

    /**
     * An array to map table names to statement columns
     *
     * @var array
     */
    protected $statementColumnTableMap;

    /**
     * A flattened array to map aliases to statement columns
     *
     * @var array
     */
    protected $statementColumnAliasMap;

    /**
     * List of column names or aliases mapped to their table where the COLLATE SQL-instruction has been removed
     *
     * This list is being populated in case of a PostgreSQL backend only,
     * to ensure case-insensitive string comparison in WHERE clauses.
     *
     * @var array
     */
    protected $caseInsensitiveColumns;

    /**
     * Create a new DB repository object
     *
     * In case $this->queryColumns has already been initialized, this initializes
     * $this->caseInsensitiveColumns in case of a PostgreSQL connection.
     *
     * @param   DbConnection    $ds     The datasource to use
     */
    public function __construct(DbConnection $ds)
    {
        parent::__construct($ds);

        if ($ds->getDbType() === 'pgsql' && $this->queryColumns !== null) {
            $this->queryColumns = $this->removeCollateInstruction($this->queryColumns);
        }
    }

    /**
     * Return the query columns being provided
     *
     * Initializes $this->caseInsensitiveColumns in case of a PostgreSQL connection.
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
     * Return the join probability rules
     *
     * Calls $this->initializeJoinProbabilities() in case $this->joinProbabilities is null.
     *
     * @return  array
     */
    public function getJoinProbabilities()
    {
        if ($this->joinProbabilities === null) {
            $this->joinProbabilities = $this->initializeJoinProbabilities();
        }

        return $this->joinProbabilities;
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize the join probabilities lazily
     *
     * @return  array
     */
    protected function initializeJoinProbabilities()
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
        foreach ($queryColumns as $table => & $columns) {
            foreach ($columns as $alias => & $column) {
                // Using a regex here because COLLATE may occur anywhere in the string
                $column = preg_replace('/ COLLATE .+$/', '', $column, -1, $count);
                if ($count > 0) {
                    $this->caseInsensitiveColumns[$table][is_string($alias) ? $alias : $column] = true;
                }
            }
        }

        return $queryColumns;
    }

    /**
     * Initialize table, column and alias maps
     *
     * @throws  ProgrammingError    In case $this->queryColumns does not provide any column information
     */
    protected function initializeAliasMaps()
    {
        parent::initializeAliasMaps();

        foreach ($this->aliasTableMap as $alias => $table) {
            if ($table !== null) {
                if (strpos($alias, '.') !== false) {
                    $prefixedAlias = str_replace('.', '_', $alias);
                } else {
                    $prefixedAlias = $table . '_' . $alias;
                }

                if (array_key_exists($prefixedAlias, $this->aliasTableMap)) {
                    if ($this->aliasTableMap[$prefixedAlias] !== null) {
                        $existingTable = $this->aliasTableMap[$prefixedAlias];
                        $existingColumn = $this->aliasColumnMap[$prefixedAlias];
                        $this->aliasTableMap[$existingTable . '.' . $prefixedAlias] = $existingTable;
                        $this->aliasColumnMap[$existingTable . '.' . $prefixedAlias] = $existingColumn;
                        $this->aliasTableMap[$prefixedAlias] = null;
                        $this->aliasColumnMap[$prefixedAlias] = null;
                    }

                    $this->aliasTableMap[$table . '.' . $prefixedAlias] = $table;
                    $this->aliasColumnMap[$table . '.' . $prefixedAlias] = $this->aliasColumnMap[$alias];
                } else {
                    $this->aliasTableMap[$prefixedAlias] = $table;
                    $this->aliasColumnMap[$prefixedAlias] = $this->aliasColumnMap[$alias];
                }
            }
        }
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
     * @param   string          $virtualTable
     *
     * @return  array|string
     */
    protected function applyTableAlias($table, $virtualTable = null)
    {
        if (! is_array($table)) {
            $tableAliases = $this->getTableAliases();
            if ($virtualTable !== null && isset($tableAliases[$virtualTable])) {
                return array($tableAliases[$virtualTable] => $table);
            }

            if (isset($tableAliases[($nonPrefixedTable = $this->removeTablePrefix($table))])) {
                return array($tableAliases[$nonPrefixedTable] => $table);
            }
        }

        return $table;
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
     * Note that the base implementation does not perform any quoting on the $table argument.
     * Pass an array with a column name (the same as in $bind) and a PDO::PARAM_* constant as value
     * as third parameter $types to define a different type than string for a particular column.
     *
     * @param   string  $table
     * @param   array   $bind
     * @param   array   $types
     *
     * @return  int             The number of affected rows
     */
    public function insert($table, array $bind, array $types = array())
    {
        $realTable = $this->clearTableAlias($this->requireTable($table));

        foreach ($types as $alias => $type) {
            unset($types[$alias]);
            $types[$this->requireStatementColumn($table, $alias)] = $type;
        }

        return $this->ds->insert($realTable, $this->requireStatementColumns($table, $bind), $types);
    }

    /**
     * Update table rows with the given data, optionally limited by using a filter
     *
     * Note that the base implementation does not perform any quoting on the $table argument.
     * Pass an array with a column name (the same as in $bind) and a PDO::PARAM_* constant as value
     * as fourth parameter $types to define a different type than string for a particular column.
     *
     * @param   string  $table
     * @param   array   $bind
     * @param   Filter  $filter
     * @param   array   $types
     *
     * @return  int             The number of affected rows
     */
    public function update($table, array $bind, Filter $filter = null, array $types = array())
    {
        $realTable = $this->clearTableAlias($this->requireTable($table));

        if ($filter) {
            $filter = $this->requireFilter($table, $filter);
        }

        foreach ($types as $alias => $type) {
            unset($types[$alias]);
            $types[$this->requireStatementColumn($table, $alias)] = $type;
        }

        return $this->ds->update($realTable, $this->requireStatementColumns($table, $bind), $filter, $types);
    }

    /**
     * Delete table rows, optionally limited by using a filter
     *
     * @param   string  $table
     * @param   Filter  $filter
     *
     * @return  int             The number of affected rows
     */
    public function delete($table, Filter $filter = null)
    {
        $realTable = $this->clearTableAlias($this->requireTable($table));

        if ($filter) {
            $filter = $this->requireFilter($table, $filter);
        }

        return $this->ds->delete($realTable, $filter);
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
    protected function getStatementAliasTableMap()
    {
        if ($this->statementAliasTableMap === null) {
            $this->initializeStatementMaps();
        }

        return $this->statementAliasTableMap;
    }

    /**
     * Return a flattened array to map statement columns to aliases
     *
     * @return  array
     */
    protected function getStatementAliasColumnMap()
    {
        if ($this->statementAliasColumnMap === null) {
            $this->initializeStatementMaps();
        }

        return $this->statementAliasColumnMap;
    }

    /**
     * Return an array to map table names to statement columns
     *
     * @return  array
     */
    protected function getStatementColumnTableMap()
    {
        if ($this->statementColumnTableMap === null) {
            $this->initializeStatementMaps();
        }

        return $this->statementColumnTableMap;
    }

    /**
     * Return a flattened array to map aliases to statement columns
     *
     * @return  array
     */
    protected function getStatementColumnAliasMap()
    {
        if ($this->statementColumnAliasMap === null) {
            $this->initializeStatementMaps();
        }

        return $this->statementColumnAliasMap;
    }

    /**
     * Initialize $this->statementAliasTableMap and $this->statementAliasColumnMap
     */
    protected function initializeStatementMaps()
    {
        $this->statementAliasTableMap = array();
        $this->statementAliasColumnMap = array();
        $this->statementColumnTableMap = array();
        $this->statementColumnAliasMap = array();
        foreach ($this->getStatementColumns() as $table => $columns) {
            foreach ($columns as $alias => $column) {
                $key = is_string($alias) ? $alias : $column;
                if (array_key_exists($key, $this->statementAliasTableMap)) {
                    if ($this->statementAliasTableMap[$key] !== null) {
                        $existingTable = $this->statementAliasTableMap[$key];
                        $existingColumn = $this->statementAliasColumnMap[$key];
                        $this->statementAliasTableMap[$existingTable . '.' . $key] = $existingTable;
                        $this->statementAliasColumnMap[$existingTable . '.' . $key] = $existingColumn;
                        $this->statementAliasTableMap[$key] = null;
                        $this->statementAliasColumnMap[$key] = null;
                    }

                    $this->statementAliasTableMap[$table . '.' . $key] = $table;
                    $this->statementAliasColumnMap[$table . '.' . $key] = $column;
                } else {
                    $this->statementAliasTableMap[$key] = $table;
                    $this->statementAliasColumnMap[$key] = $column;
                }

                if (array_key_exists($column, $this->statementColumnTableMap)) {
                    if ($this->statementColumnTableMap[$column] !== null) {
                        $existingTable = $this->statementColumnTableMap[$column];
                        $existingAlias = $this->statementColumnAliasMap[$column];
                        $this->statementColumnTableMap[$existingTable . '.' . $column] = $existingTable;
                        $this->statementColumnAliasMap[$existingTable . '.' . $column] = $existingAlias;
                        $this->statementColumnTableMap[$column] = null;
                        $this->statementColumnAliasMap[$column] = null;
                    }

                    $this->statementColumnTableMap[$table . '.' . $column] = $table;
                    $this->statementColumnAliasMap[$table . '.' . $column] = $key;
                } else {
                    $this->statementColumnTableMap[$column] = $table;
                    $this->statementColumnAliasMap[$column] = $key;
                }
            }
        }
    }

    /**
     * Return whether this repository is capable of converting values for the given table and optional column
     *
     * This does not check whether any conversion for the given table is available if $column is not given, as it
     * may be possible that columns from another table where joined in which would otherwise not being converted.
     *
     * @param   string  $table
     * @param   string  $column
     *
     * @return  bool
     */
    public function providesValueConversion($table, $column = null)
    {
        if ($column !== null) {
            if ($column instanceof Zend_Db_Expr) {
                return false;
            }

            if ($this->validateQueryColumnAssociation($table, $column)) {
                return parent::providesValueConversion($table, $column);
            }

            if (($tableName = $this->findTableName($column, $table))) {
                return parent::providesValueConversion($tableName, $column);
            }

            return false;
        }

        $conversionRules = $this->getConversionRules();
        return !empty($conversionRules);
    }

    /**
     * Return the name of the conversion method for the given alias or column name and context
     *
     * If a query column or a filter column, which is part of a query filter, needs to be converted,
     * you'll need to pass $query, otherwise the column is considered a statement column.
     *
     * @param   string              $table      The datasource's table
     * @param   string              $name       The alias or column name for which to return a conversion method
     * @param   string              $context    The context of the conversion: persist or retrieve
     * @param   RepositoryQuery     $query      If given the column is considered a query column,
     *                                          statement column otherwise
     *
     * @return  string
     *
     * @throws  ProgrammingError    In case a conversion rule is found but not any conversion method
     */
    protected function getConverter($table, $name, $context, RepositoryQuery $query = null)
    {
        if ($name instanceof Zend_Db_Expr) {
            return;
        }

        if (! ($query !== null && $this->validateQueryColumnAssociation($table, $name))
            && !($query === null && $this->validateStatementColumnAssociation($table, $name))
        ) {
            $table = $this->findTableName($name, $table);
            if (! $table) {
                if ($query !== null) {
                    // It may be an aliased Zend_Db_Expr
                    $desiredColumns = $query->getColumns();
                    if (isset($desiredColumns[$name]) && $desiredColumns[$name] instanceof Zend_Db_Expr) {
                        return;
                    }
                }

                throw new ProgrammingError('Column name validation seems to have failed. Did you require the column?');
            }
        }

        return parent::getConverter($table, $name, $context, $query);
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
        $virtualTable = null;
        $statementColumns = $this->getStatementColumns();
        if (! isset($statementColumns[$table])) {
            $newTable = parent::requireTable($table);
            if ($newTable !== $table) {
                $virtualTable = $table;
            }

            $table = $newTable;
        } else {
            $virtualTables = $this->getVirtualTables();
            if (isset($virtualTables[$table])) {
                $virtualTable = $table;
                $table = $virtualTables[$table];
            }
        }

        return $this->prependTablePrefix($this->applyTableAlias($table, $virtualTable));
    }

    /**
     * Return the alias for the given table or null if none has been defined
     *
     * @param   string  $table
     *
     * @return  string|null
     */
    public function resolveTableAlias($table)
    {
        $tableAliases = $this->getTableAliases();
        if (isset($tableAliases[$table])) {
            return $tableAliases[$table];
        }
    }

    /**
     * Return the alias for the given query column name or null in case the query column name does not exist
     *
     * @param   string  $table
     * @param   string  $column
     *
     * @return  string|null
     */
    public function reassembleQueryColumnAlias($table, $column)
    {
        $alias = parent::reassembleQueryColumnAlias($table, $column);
        if ($alias === null
            && !$this->validateQueryColumnAssociation($table, $column)
            && ($tableName = $this->findTableName($column, $table))
        ) {
            return parent::reassembleQueryColumnAlias($tableName, $column);
        }

        return $alias;
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
     * @throws  ProgrammingError            In case the given column is not found in $table and cannot be joined in
     */
    public function requireQueryColumn($table, $name, RepositoryQuery $query = null)
    {
        if ($name instanceof Zend_Db_Expr) {
            return $name;
        }

        if ($query === null || $this->validateQueryColumnAssociation($table, $name)) {
            return parent::requireQueryColumn($table, $name, $query);
        }

        $column = $this->joinColumn($name, $table, $query);
        if ($column === null) {
            if ($query !== null) {
                // It may be an aliased Zend_Db_Expr
                $desiredColumns = $query->getColumns();
                if (isset($desiredColumns[$name]) && $desiredColumns[$name] instanceof Zend_Db_Expr) {
                    $column = $desiredColumns[$name];
                }
            }

            if ($column === null) {
                throw new ProgrammingError(
                    'Unable to find a valid table for column "%s" to join into "%s"',
                    $name,
                    $table
                );
            }
        }

        return $column;
    }

    /**
     * Validate that the given column is a valid filter target and return it or the actual name if it's an alias
     *
     * Attempts to join the given column from a different table if its association to the given table cannot be
     * verified. In case of a PostgreSQL connection and if a COLLATE SQL-instruction is part of the resolved column,
     * this applies LOWER() on the column and, if given, strtolower() on the filter's expression.
     *
     * @param   string              $table  The table where to look for the column or alias
     * @param   string              $name   The name or alias of the column to validate
     * @param   RepositoryQuery     $query  An optional query to pass as context,
     *                                      if not given the column is considered being used for a statement filter
     * @param   FilterExpression    $filter An optional filter to pass as context
     *
     * @return  string                      The given column's name
     *
     * @throws  QueryException              In case the given column is not a valid filter column
     * @throws  ProgrammingError            In case the given column is not found in $table and cannot be joined in
     */
    public function requireFilterColumn($table, $name, RepositoryQuery $query = null, FilterExpression $filter = null)
    {
        if ($name instanceof Zend_Db_Expr) {
            return $name;
        }

        $joined = false;
        if ($query === null) {
            $column = $this->requireStatementColumn($table, $name);
        } elseif ($this->validateQueryColumnAssociation($table, $name)) {
            $column = parent::requireFilterColumn($table, $name, $query, $filter);
        } else {
            $column = $this->joinColumn($name, $table, $query);
            if ($column === null) {
                if ($query !== null) {
                    // It may be an aliased Zend_Db_Expr
                    $desiredColumns = $query->getColumns();
                    if (isset($desiredColumns[$name]) && $desiredColumns[$name] instanceof Zend_Db_Expr) {
                        $column = $desiredColumns[$name];
                    }
                }

                if ($column === null) {
                    throw new ProgrammingError(
                        'Unable to find a valid table for column "%s" to join into "%s"',
                        $name,
                        $table
                    );
                }
            } else {
                $joined = true;
            }
        }

        if (! empty($this->caseInsensitiveColumns)) {
            if ($joined) {
                $table = $this->findTableName($name, $table);
            }

            if ($column === $name) {
                if ($query === null) {
                    $name = $this->reassembleStatementColumnAlias($table, $name);
                } else {
                    $name = $this->reassembleQueryColumnAlias($table, $name);
                }
            }

            if (isset($this->caseInsensitiveColumns[$table][$name])) {
                $column = 'LOWER(' . $column . ')';
                if ($filter !== null) {
                    $expression = $filter->getExpression();
                    if (is_array($expression)) {
                        $filter->setExpression(array_map('strtolower', $expression));
                    } else {
                        $filter->setExpression(strtolower($expression));
                    }
                }
            }
        }

        return $column;
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
        $statementAliasColumnMap = $this->getStatementAliasColumnMap();
        if (isset($statementAliasColumnMap[$alias])) {
            return $statementAliasColumnMap[$alias];
        }

        $prefixedAlias = $table . '.' . $alias;
        if (isset($statementAliasColumnMap[$prefixedAlias])) {
            return $statementAliasColumnMap[$prefixedAlias];
        }
    }

    /**
     * Return the alias for the given statement column name or null in case the statement column does not exist
     *
     * @param   string  $table
     * @param   string  $column
     *
     * @return  string|null
     */
    public function reassembleStatementColumnAlias($table, $column)
    {
        $statementColumnAliasMap = $this->getStatementColumnAliasMap();
        if (isset($statementColumnAliasMap[$column])) {
            return $statementColumnAliasMap[$column];
        }

        $prefixedColumn = $table . '.' . $column;
        if (isset($statementColumnAliasMap[$prefixedColumn])) {
            return $statementColumnAliasMap[$prefixedColumn];
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
        $statementAliasTableMap = $this->getStatementAliasTableMap();
        if (isset($statementAliasTableMap[$alias])) {
            return $statementAliasTableMap[$alias] === $table;
        }

        $prefixedAlias = $table . '.' . $alias;
        if (isset($statementAliasTableMap[$prefixedAlias])) {
            return true;
        }

        $statementColumnTableMap = $this->getStatementColumnTableMap();
        if (isset($statementColumnTableMap[$alias])) {
            return $statementColumnTableMap[$alias] === $table;
        }

        return isset($statementColumnTableMap[$prefixedAlias]);
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
        if (($this->resolveStatementColumnAlias($table, $name) === null
             && $this->reassembleStatementColumnAlias($table, $name) === null)
            || !$this->validateStatementColumnAssociation($table, $name)
        ) {
            return parent::hasStatementColumn($table, $name);
        }

        return true;
    }

    /**
     * Validate that the given column is a valid statement column and return it or the actual name if it's an alias
     *
     * @param   string  $table          The table for which to require the column
     * @param   string  $name           The name or alias of the column to validate
     *
     * @return  string                  The given column's name
     *
     * @throws  StatementException      In case the given column is not a statement column
     */
    public function requireStatementColumn($table, $name)
    {
        if (($column = $this->resolveStatementColumnAlias($table, $name)) !== null) {
            $alias = $name;
        } elseif (($alias = $this->reassembleStatementColumnAlias($table, $name)) !== null) {
            $column = $name;
        } else {
            return parent::requireStatementColumn($table, $name);
        }

        if (! $this->validateStatementColumnAssociation($table, $alias)) {
            throw new StatementException('Statement column "%s" not found in table "%s"', $name, $table);
        }

        return $column;
    }

    /**
     * Join alias or column $name into $table using $query
     *
     * Attempts to find a valid table for the given alias or column name and a method labelled join<TableName>
     * to process the actual join logic. If neither of those is found, null is returned.
     * The method is called with the same parameters but in reversed order.
     *
     * @param   string              $name       The alias or column name to join into $target
     * @param   string              $target     The table to join $name into
     * @param   RepositoryQUery     $query      The query to apply the JOIN-clause on
     *
     * @return  string|null                     The resolved alias or $name, null if no join logic is found
     */
    public function joinColumn($name, $target, RepositoryQuery $query)
    {
        if (! ($tableName = $this->findTableName($name, $target))) {
            return;
        }

        if (($column = $this->resolveQueryColumnAlias($tableName, $name)) === null) {
            $column = $name;
        }

        if (($joinIdentifier = $this->resolveTableAlias($tableName)) === null) {
            $joinIdentifier = $this->prependTablePrefix($tableName);
        }
        if ($query->getQuery()->hasJoinedTable($joinIdentifier)) {
            return $column;
        }

        $joinMethod = 'join' . StringHelper::cname($tableName);
        if (! method_exists($this, $joinMethod)) {
            throw new ProgrammingError(
                'Unable to join table "%s" into "%s". Method "%s" not found',
                $tableName,
                $target,
                $joinMethod
            );
        }

        $this->$joinMethod($query, $target, $name);
        return $column;
    }

    /**
     * Return the table name for the given alias or column name
     *
     * @param   string  $column     The alias or column name
     * @param   string  $origin     The base table of a SELECT query
     *
     * @return  string|null         null in case no table is found
     */
    protected function findTableName($column, $origin)
    {
        // First, try to produce an exact match since it's faster and cheaper
        $aliasTableMap = $this->getAliasTableMap();
        if (isset($aliasTableMap[$column])) {
            $table = $aliasTableMap[$column];
        } else {
            $columnTableMap = $this->getColumnTableMap();
            if (isset($columnTableMap[$column])) {
                $table = $columnTableMap[$column];
            }
        }

        // But only return it if it's a probable join...
        $joinProbabilities = $this->getJoinProbabilities();
        if (isset($joinProbabilities[$origin])) {
            $probableJoins = $joinProbabilities[$origin];
        }

        // ...if probability can be determined
        if (isset($table) && (empty($probableJoins) || in_array($table, $probableJoins, true))) {
            return $table;
        }

        // Without a proper exact match, there is only one fast and cheap way to find a suitable table..
        if (! empty($probableJoins)) {
            foreach ($probableJoins as $table) {
                if (isset($aliasTableMap[$table . '.' . $column])) {
                    return $table;
                }
            }
        }

        // Last chance to find a table. Though, this usually ends up with a QueryException..
        foreach ($aliasTableMap as $prefixedAlias => $table) {
            if (strpos($prefixedAlias, '.') !== false) {
                list($_, $alias) = explode('.', $prefixedAlias, 2);
                if ($alias === $column) {
                    return $table;
                }
            }
        }
    }
}
