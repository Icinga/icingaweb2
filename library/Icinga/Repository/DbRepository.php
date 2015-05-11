<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Repository;

use Icinga\Data\Extensible;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Reducible;
use Icinga\Data\Updatable;
use Icinga\Exception\IcingaException;
use Icinga\Exception\ProgrammingError;

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
     * Insert a table row with the given data
     *
     * @param   string  $table
     * @param   array   $bind
     */
    public function insert($table, array $bind)
    {
        $this->ds->insert($this->prependTablePrefix($table), $this->requireStatementColumns($bind));
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
            $this->requireFilter($filter);
        }

        $this->ds->update($this->prependTablePrefix($table), $this->requireStatementColumns($bind), $filter);
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
            $this->requireFilter($filter);
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
        foreach ($this->getStatementColumns() as $table => $columns) {
            foreach ($columns as $alias => $column) {
                if (! is_string($alias)) {
                    $this->statementTableMap[$column] = $table;
                    $this->statementColumnMap[$column] = $column;
                } else {
                    $this->statementTableMap[$alias] = $table;
                    $this->statementColumnMap[$alias] = $column;
                }
            }
        }
    }

    /**
     * Return whether the given column name or alias is a valid statement column
     *
     * @param   string  $name   The column name or alias to check
     *
     * @return  bool
     */
    public function hasStatementColumn($name)
    {
        $statementColumnMap = $this->getStatementColumnMap();
        if (! array_key_exists($name, $statementColumnMap)) {
            return parent::hasStatementColumn($name);
        }

        return true;
    }

    /**
     * Validate that the given column is a valid statement column and return it or the actual name if it's an alias
     *
     * @param   string  $name       The name or alias of the column to validate
     *
     * @return  string              The given column's name
     *
     * @throws  QueryException      In case the given column is not a statement column
     */
    public function requireStatementColumn($name)
    {
        $statementColumnMap = $this->getStatementColumnMap();
        if (! array_key_exists($name, $statementColumnMap)) {
            return parent::requireStatementColumn($name);
        }

        return $statementColumnMap[$name];
    }
}
