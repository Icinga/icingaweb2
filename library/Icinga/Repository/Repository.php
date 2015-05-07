<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Repository;

use Icinga\Data\Selectable;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\QueryException;

/**
 * Abstract base class for concrete repository implementations
 *
 * To utilize this class and its features, the following is required:
 * <ul>
 *  <li>Concrete implementations need to initialize Repository::$queryColumns</li>
 *  <li>The datasource passed to a repository must implement the Selectable interface</li>
 *  <li>The datasource must yield an instance of QueryInterface when its select() method is called</li>
 * </ul>
 */
abstract class Repository implements Selectable
{
    /**
     * The name of this repository
     *
     * @var string
     */
    protected $name;

    /**
     * The datasource being used
     *
     * @var Selectable
     */
    protected $ds;

    /**
     * The base table name this repository is responsible for
     *
     * This will be automatically set to the first key of $queryColumns if not explicitly set.
     *
     * @var mixed
     */
    protected $baseTable;

    /**
     * The query columns being provided
     *
     * This must be initialized by concrete repository implementations, in the following format
     * <pre><code>
     *  array(
     *      'baseTable' => array(
     *          'column1',
     *          'alias1' => 'column2',
     *          'alias2' => 'column3'
     *      )
     *  )
     * <pre><code>
     *
     * @var array
     */
    protected $queryColumns;

    /**
     * The columns (or aliases) which are not permitted to be queried. (by design)
     *
     * @var array   An array of strings
     */
    protected $filterColumns;

    /**
     * The default sort rules to be applied on a query
     *
     * This may be initialized by concrete repository implementations, in the following format
     * <pre><code>
     *  array(
     *      'alias_or_column_name' => array(
     *          'order'     => 'asc'
     *      ),
     *      'alias_or_column_name' => array(
     *          'columns'   => array(
     *              'once_more_the_alias_or_column_name_as_in_the_parent_key',
     *              'an_additional_alias_or_column_name_with_a_specific_direction asc'
     *          ),
     *          'order'     => 'desc'
     *      ),
     *      'alias_or_column_name' => array(
     *          'columns'   => array('a_different_alias_or_column_name_designated_to_act_as_the_only_sort_column')
     *          // Ascendant sort by default
     *      )
     *  )
     * <pre><code>
     * Note that it's mandatory to supply the alias name in case there is one.
     *
     * @var array
     */
    protected $sortRules;

    /**
     * An array to map table names to aliases
     *
     * @var array
     */
    protected $aliasTableMap;

    /**
     * A flattened array to map query columns to aliases
     *
     * @var array
     */
    protected $aliasColumnMap;

    /**
     * Create a new repository object
     *
     * @param   Selectable  $ds     The datasource to use
     */
    public function __construct(Selectable $ds)
    {
        $this->ds = $ds;
        $this->aliasTableMap = array();
        $this->aliasColumnMap = array();

        $this->init();

        if ($this->filterColumns === null) {
            $this->filterColumns = $this->getFilterColumns();
        }

        if ($this->sortRules === null) {
            $this->sortRules = $this->getSortRules();
        }
    }

    /**
     * Initialize this repository
     *
     * Supposed to be overwritten by concrete repository implementations.
     */
    protected function init()
    {

    }

    /**
     * Set this repository's name
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Return this repository's name
     *
     * In case no name has been explicitly set yet, the class name is returned.
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name ?: __CLASS__;
    }

    /**
     * Return the datasource being used
     *
     * @return  Selectable
     */
    public function getDataSource()
    {
        return $this->ds;
    }

    /**
     * Return the base table name this repository is responsible for
     *
     * @return  mixed
     *
     * @throws  ProgrammingError    In case no base table name has been set and
     *                               $this->queryColumns does not provide one either
     */
    public function getBaseTable()
    {
        if ($this->baseTable === null) {
            $queryColumns = $this->queryColumns; // Copy because of reset()
            reset($queryColumns);
            $this->baseTable = key($queryColumns);
            if (is_int($this->baseTable) || !is_array($queryColumns[$this->baseTable])) {
                throw new ProgrammingError('"%s" is not a valid base table', $this->baseTable);
            }
        }

        return $this->baseTable;
    }

    /**
     * Return the columns (or aliases) which are not permitted to be queried
     *
     * @return  array
     */
    public function getFilterColumns()
    {
        if ($this->filterColumns !== null) {
            return $this->filterColumns;
        }

        return array();
    }

    /**
     * Return the default sort rules to be applied on a query
     *
     * @return  array
     */
    public function getSortRules()
    {
        if ($this->sortRules !== null) {
            return $this->sortRules;
        }

        return array();
    }

    /**
     * Return a new query for the given columns
     *
     * @param   array   $columns    The desired columns, if null all columns will be queried
     *
     * @return  RepositoryQuery
     *
     * @throws  ProgrammingError    In case $this->queryColumns has not been initialized yet
     */
    public function select(array $columns = null)
    {
        if (empty($this->queryColumns)) {
            throw new ProgrammingError('Repositories are required to initialize $this->queryColumns first');
        }

        $this->initializeAliasMaps();

        $query = new RepositoryQuery($this);
        $query->from($this->getBaseTable(), $columns);
        return $query;
    }

    /**
     * Initialize $this->aliasTableMap and $this->aliasColumnMap
     */
    protected function initializeAliasMaps()
    {
        if (! empty($this->aliasColumnMap)) {
            return;
        }

        foreach ($this->queryColumns as $table => $columns) {
            foreach ($columns as $alias => $column) {
                if (! is_string($alias)) {
                    $this->aliasTableMap[$column] = $table;
                    $this->aliasColumnMap[$column] = $column;
                } else {
                    $this->aliasTableMap[$alias] = $table;
                    $this->aliasColumnMap[$alias] = preg_replace('~\n\s*~', ' ', $column);
                }
            }
        }
    }

    /**
     * Return this repository's query columns mapped to their respective aliases
     *
     * @return  array
     */
    public function requireAllQueryColumns()
    {
        $map = array();
        foreach ($this->aliasColumnMap as $alias => $_) {
            if ($this->hasQueryColumn($alias)) {
                // Just in case $this->requireQueryColumn has been overwritten and there is some magic going on
                $map[$alias] = $this->requireQueryColumn($alias);
            }
        }

        return $map;
    }

    /**
     * Return whether the given column name or alias is a valid query column
     *
     * @param   string  $name   The column name or alias to check
     *
     * @return  bool
     */
    public function hasQueryColumn($name)
    {
        return array_key_exists($name, $this->aliasColumnMap) && !in_array($name, $this->filterColumns);
    }

    /**
     * Validate that the given column is a valid query target and return it or the actual name if it's an alias
     *
     * @param   string  $name       The name or alias of the column to validate
     *
     * @return  string              The given column's name
     *
     * @throws  QueryException      In case the given column is not a valid query column
     */
    public function requireQueryColumn($name)
    {
        if (in_array($name, $this->filterColumns)) {
            throw new QueryException(t('Filter column "%s" cannot be queried'), $name);
        }
        if (! array_key_exists($name, $this->aliasColumnMap)) {
            throw new QueryException(t('Query column "%s" not found'), $name);
        }

        return $this->aliasColumnMap[$name];
    }

    /**
     * Return whether the given column name or alias is a valid filter column
     *
     * @param   string  $name   The column name or alias to check
     *
     * @return  bool
     */
    public function hasFilterColumn($name)
    {
        return array_key_exists($name, $this->aliasColumnMap);
    }

    /**
     * Validate that the given column is a valid filter target and return it or the actual name if it's an alias
     *
     * @param   string  $name       The name or alias of the column to validate
     *
     * @return  string              The given column's name
     *
     * @throws  QueryException      In case the given column is not a valid filter column
     */
    public function requireFilterColumn($name)
    {
        if (! array_key_exists($name, $this->aliasColumnMap)) {
            throw new QueryException(t('Filter column "%s" not found'), $name);
        }

        return $this->aliasColumnMap[$name];
    }
}
