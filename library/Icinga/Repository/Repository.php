<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Repository;

use Icinga\Application\Logger;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Selectable;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\QueryException;
use Icinga\Exception\StatementException;

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
     * The value conversion rules to apply on a query
     *
     * This may be initialized by concrete repository implementations and describes for which aliases or column
     * names what type of conversion is available. For entries, where the key is the alias/column and the value
     * is the type identifier, the repository attempts to find a conversion method for the alias/column first and,
     * if none is found, then for the type. If an entry only provides a value, which is the alias/column, the
     * repository only attempts to find a conversion method for the alias/column. The name of a conversion method
     * is expected to be declared using lowerCamelCase. (e.g. user_name will be translated to persistUserName and
     * groupname will be translated to retrieveGroupname)
     *
     * @var array
     */
    protected $conversionRules;

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
            $queryColumns = $this->getQueryColumns();
            reset($queryColumns);
            $this->baseTable = key($queryColumns);
            if (is_int($this->baseTable) || !is_array($queryColumns[$this->baseTable])) {
                throw new ProgrammingError('"%s" is not a valid base table', $this->baseTable);
            }
        }

        return $this->baseTable;
    }

    /**
     * Return the query columns being provided
     *
     * Calls $this->initializeQueryColumns() in case $this->queryColumns is null.
     *
     * @return  array
     */
    public function getQueryColumns()
    {
        if ($this->queryColumns === null) {
            $this->queryColumns = $this->initializeQueryColumns();
        }

        return $this->queryColumns;
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize the query columns lazily
     *
     * @return  array
     */
    protected function initializeQueryColumns()
    {
        return array();
    }

    /**
     * Return the columns (or aliases) which are not permitted to be queried
     *
     * Calls $this->initializeFilterColumns() in case $this->filterColumns is null.
     *
     * @return  array
     */
    public function getFilterColumns()
    {
        if ($this->filterColumns === null) {
            $this->filterColumns = $this->initializeFilterColumns();
        }

        return $this->filterColumns;
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize the filter columns lazily
     *
     * @return  array
     */
    protected function initializeFilterColumns()
    {
        return array();
    }

    /**
     * Return the default sort rules to be applied on a query
     *
     * Calls $this->initializeSortRules() in case $this->sortRules is null.
     *
     * @return  array
     */
    public function getSortRules()
    {
        if ($this->sortRules === null) {
            $this->sortRules = $this->initializeSortRules();
        }

        return $this->sortRules;
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize the sort rules lazily
     *
     * @return  array
     */
    protected function initializeSortRules()
    {
        return array();
    }

    /**
     * Return the value conversion rules to apply on a query
     *
     * Calls $this->initializeConversionRules() in case $this->conversionRules is null.
     *
     * @return  array
     */
    public function getConversionRules()
    {
        if ($this->conversionRules === null) {
            $this->conversionRules = $this->initializeConversionRules();
        }

        return $this->conversionRules;
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize the conversion rules lazily
     *
     * @return  array
     */
    protected function initializeConversionRules()
    {
        return array();
    }

    /**
     * Return an array to map table names to aliases
     *
     * @return  array
     */
    protected function getAliasTableMap()
    {
        if (empty($this->aliasTableMap)) {
            $this->initializeAliasMaps();
        }

        return $this->aliasTableMap;
    }

    /**
     * Return a flattened array to map query columns to aliases
     *
     * @return  array
     */
    protected function getAliasColumnMap()
    {
        if (empty($this->aliasColumnMap)) {
            $this->initializeAliasMaps();
        }

        return $this->aliasColumnMap;
    }

    /**
     * Initialize $this->aliasTableMap and $this->aliasColumnMap
     *
     * @throws  ProgrammingError    In case $this->queryColumns does not provide any column information
     */
    protected function initializeAliasMaps()
    {
        $queryColumns = $this->getQueryColumns();
        if (empty($queryColumns)) {
            throw new ProgrammingError('Repositories are required to initialize $this->queryColumns first');
        }

        foreach ($queryColumns as $table => $columns) {
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
     * Return a new query for the given columns
     *
     * @param   array   $columns    The desired columns, if null all columns will be queried
     *
     * @return  RepositoryQuery
     */
    public function select(array $columns = null)
    {
        $query = new RepositoryQuery($this);
        $query->from($this->getBaseTable(), $columns);
        return $query;
    }

    /**
     * Return whether this repository is capable of converting values
     *
     * @return  bool
     */
    public function providesValueConversion()
    {
        $conversionRules = $this->getConversionRules();
        return !empty($conversionRules);
    }

    /**
     * Convert a value supposed to be transmitted to the data source
     *
     * @param   string  $name       The alias or column name
     * @param   mixed   $value      The value to convert
     *
     * @return  mixed               If conversion was possible, the converted value, otherwise the unchanged value
     */
    public function persistColumn($name, $value)
    {
        $converter = $this->getConverter($name, 'persist');
        if ($converter !== null) {
            $value = $this->$converter($value);
        }

        return $value;
    }

    /**
     * Convert a value which was fetched from the data source
     *
     * @param   string  $name       The alias or column name
     * @param   mixed   $value      The value to convert
     *
     * @return  mixed               If conversion was possible, the converted value, otherwise the unchanged value
     */
    public function retrieveColumn($name, $value)
    {
        $converter = $this->getConverter($name, 'retrieve');
        if ($converter !== null) {
            $value = $this->$converter($value);
        }

        return $value;
    }

    /**
     * Return the name of the conversion method for the given alias or column name and context
     *
     * @param   string  $name       The alias or column name for which to return a conversion method
     * @param   string  $context    The context of the conversion: persist or retrieve
     *
     * @return  string
     *
     * @throws  ProgrammingError    In case a conversion rule is found but not any conversion method
     */
    protected function getConverter($name, $context)
    {
        $conversionRules = $this->getConversionRules();

        // Check for a conversion method for the alias/column first
        if (array_key_exists($name, $conversionRules) || in_array($name, $conversionRules)) {
            $methodName = $context . join('', array_map('ucfirst', explode('_', $name)));
            if (method_exists($this, $methodName)) {
                return $methodName;
            }
        }

        // The conversion method for the type is just a fallback, but it is required to exist if defined
        if (isset($conversionRules[$name])) {
            $identifier = join('', array_map('ucfirst', explode('_', $conversionRules[$name])));
            if (! method_exists($this, $context . $identifier)) {
                // Do not throw an error in case at least one conversion method exists
                if (! method_exists($this, ($context === 'persist' ? 'retrieve' : 'persist') . $identifier)) {
                    throw new ProgrammingError(
                        'Cannot find any conversion method for type "%s"'
                        . '. Add a proper conversion method or remove the type definition',
                        $conversionRules[$name]
                    );
                }

                Logger::debug(
                    'Conversion method "%s" for type definition "%s" does not exist in repository "%s".',
                    $context . $identifier,
                    $conversionRules[$name],
                    $this->getName()
                );
            } else {
                return $context . $identifier;
            }
        }
    }

    /**
     * Recurse the given filter, require each filter column and convert all values
     *
     * @param   Filter  $filter
     */
    public function requireFilter(Filter $filter)
    {
        if ($filter->isExpression()) {
            $column = $filter->getColumn();
            $filter->setColumn($this->requireFilterColumn($column));
            $filter->setExpression($this->persistColumn($column, $filter->getExpression()));
        } elseif ($filter->isChain()) {
            foreach ($filter->filters() as $chainOrExpression) {
                $this->requireFilter($chainOrExpression);
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
        foreach ($this->getAliasColumnMap() as $alias => $_) {
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
        return array_key_exists($name, $this->getAliasColumnMap()) && !in_array($name, $this->getFilterColumns());
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
        if (in_array($name, $this->getFilterColumns())) {
            throw new QueryException(t('Filter column "%s" cannot be queried'), $name);
        }

        $aliasColumnMap = $this->getAliasColumnMap();
        if (! array_key_exists($name, $aliasColumnMap)) {
            throw new QueryException(t('Query column "%s" not found'), $name);
        }

        return $aliasColumnMap[$name];
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
        return array_key_exists($name, $this->getAliasColumnMap());
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
        $aliasColumnMap = $this->getAliasColumnMap();
        if (! array_key_exists($name, $aliasColumnMap)) {
            throw new QueryException(t('Filter column "%s" not found'), $name);
        }

        return $aliasColumnMap[$name];
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
        return $this->hasQueryColumn($name);
    }

    /**
     * Validate that the given column is a valid statement column and return it or the actual name if it's an alias
     *
     * @param   string  $name       The name or alias of the column to validate
     *
     * @return  string              The given column's name
     *
     * @throws  StatementException  In case the given column is not a statement column
     */
    public function requireStatementColumn($name)
    {
        if (in_array($name, $this->filterColumns)) {
            throw new StatementException('Filter column "%s" cannot be referenced in a statement', $name);
        }

        $aliasColumnMap = $this->getAliasColumnMap();
        if (! array_key_exists($name, $aliasColumnMap)) {
            throw new StatementException('Statement column "%s" not found', $name);
        }

        return $aliasColumnMap[$name];
    }

    /**
     * Resolve the given aliases or column names supposed to be persisted and convert their values
     *
     * @param   array   $data
     *
     * @return  array
     */
    public function requireStatementColumns(array $data)
    {
        $resolved = array();
        foreach ($data as $alias => $value) {
            $resolved[$this->requireStatementColumn($alias)] = $this->persistColumn($alias, $value);
        }

        return $resolved;
    }
}
