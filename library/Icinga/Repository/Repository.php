<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Repository;

use DateTime;
use Icinga\Application\Logger;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterExpression;
use Icinga\Data\Selectable;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\QueryException;
use Icinga\Exception\StatementException;
use Icinga\Util\ASN1;
use Icinga\Util\StringHelper;
use InvalidArgumentException;

/**
 * Abstract base class for concrete repository implementations
 *
 * To utilize this class and its features, the following is required:
 * <ul>
 *  <li>Concrete implementations need to initialize Repository::$queryColumns</li>
 *  <li>The datasource passed to a repository must implement the Selectable interface</li>
 *  <li>The datasource must yield an instance of Queryable when its select() method is called</li>
 * </ul>
 */
abstract class Repository implements Selectable
{
    /**
     * The format to use when converting values of type date_time
     */
    const DATETIME_FORMAT = 'd/m/Y g:i A';

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
     * @var string
     */
    protected $baseTable;

    /**
     * The virtual tables being provided
     *
     * This may be initialized by concrete repository implementations with an array
     * where a key is the name of a virtual table and its value the real table name.
     *
     * @var array
     */
    protected $virtualTables;

    /**
     * The query columns being provided
     *
     * This must be initialized by concrete repository implementations, in the following format
     * <code>
     *  array(
     *      'baseTable' => array(
     *          'column1',
     *          'alias1' => 'column2',
     *          'alias2' => 'column3'
     *      )
     *  )
     * </code>
     *
     * @var array
     */
    protected $queryColumns;

    /**
     * The columns (or aliases) which are not permitted to be queried
     *
     * Blacklisted query columns can still occur in a filter expression or sort rule.
     *
     * @var array   An array of strings
     */
    protected $blacklistedQueryColumns;

    /**
     * Whether the blacklisted query columns are in the legacy format
     *
     * @var bool
     */
    protected $legacyBlacklistedQueryColumns;

    /**
     * The filter columns being provided
     *
     * This may be intialized by concrete repository implementations, in the following format
     * <code>
     *  array(
     *      'alias_or_column_name',
     *      'label_to_show_in_the_filter_editor' => 'alias_or_column_name'
     *  )
     * </code>
     *
     * @var array
     */
    protected $filterColumns;

    /**
     * Whether the provided filter columns are in the legacy format
     *
     * @var bool
     */
    protected $legacyFilterColumns;

    /**
     * The search columns (or aliases) being provided
     *
     * @var array   An array of strings
     */
    protected $searchColumns;

    /**
     * Whether the provided search columns are in the legacy format
     *
     * @var bool
     */
    protected $legacySearchColumns;

    /**
     * The sort rules to be applied on a query
     *
     * This may be initialized by concrete repository implementations, in the following format
     * <code>
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
     * </code>
     * Note that it's mandatory to supply the alias name in case there is one.
     *
     * @var array
     */
    protected $sortRules;

    /**
     * Whether the provided sort rules are in the legacy format
     *
     * @var bool
     */
    protected $legacySortRules;

    /**
     * The value conversion rules to apply on a query or statement
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
     * An array to map table names to query columns
     *
     * @var array
     */
    protected $columnTableMap;

    /**
     * A flattened array to map aliases to query columns
     *
     * @var array
     */
    protected $columnAliasMap;

    /**
     * Create a new repository object
     *
     * @param   Selectable|null $ds The datasource to use.
     *                              Only pass null if you have overridden {@link getDataSource()}!
     */
    public function __construct(Selectable $ds = null)
    {
        $this->ds = $ds;
        $this->aliasTableMap = array();
        $this->aliasColumnMap = array();
        $this->columnTableMap = array();
        $this->columnAliasMap = array();

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
     * Return the datasource being used for the given table
     *
     * @param   string  $table
     *
     * @return  Selectable
     *
     * @throws  ProgrammingError    In case no datasource is available
     */
    public function getDataSource($table = null)
    {
        if ($this->ds === null) {
            throw new ProgrammingError(
                'No data source available. It is required to either pass it'
                . ' at initialization time or by overriding this method.'
            );
        }

        return $this->ds;
    }

    /**
     * Return the base table name this repository is responsible for
     *
     * @return  string
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
     * Return the virtual tables being provided
     *
     * Calls $this->initializeVirtualTables() in case $this->virtualTables is null.
     *
     * @return  array
     */
    public function getVirtualTables()
    {
        if ($this->virtualTables === null) {
            $this->virtualTables = $this->initializeVirtualTables();
        }

        return $this->virtualTables;
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize the virtual tables lazily
     *
     * @return  array
     */
    protected function initializeVirtualTables()
    {
        return array();
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
     * Calls $this->initializeBlacklistedQueryColumns() in case $this->blacklistedQueryColumns is null.
     *
     * @param   string  $table
     *
     * @return  array
     */
    public function getBlacklistedQueryColumns($table = null)
    {
        if ($this->blacklistedQueryColumns === null) {
            $this->legacyBlacklistedQueryColumns = false;

            $blacklistedQueryColumns = $this->initializeBlacklistedQueryColumns($table);
            if (is_int(key($blacklistedQueryColumns))) {
                $this->blacklistedQueryColumns[$table] = $blacklistedQueryColumns;
            } else {
                $this->blacklistedQueryColumns = $blacklistedQueryColumns;
            }
        } elseif ($this->legacyBlacklistedQueryColumns === null) {
            $this->legacyBlacklistedQueryColumns = is_int(key($this->blacklistedQueryColumns));
        }

        if ($this->legacyBlacklistedQueryColumns) {
            return $this->blacklistedQueryColumns;
        } elseif (! isset($this->blacklistedQueryColumns[$table])) {
            $this->blacklistedQueryColumns[$table] = $this->initializeBlacklistedQueryColumns($table);
        }

        return $this->blacklistedQueryColumns[$table];
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize the
     * blacklisted query columns lazily or dependent on a query's current base table
     *
     * @param   string  $table
     *
     * @return  array
     */
    protected function initializeBlacklistedQueryColumns()
    {
        // $table is not part of the signature due to PHP strict standards
        return array();
    }

    /**
     * Return the filter columns being provided
     *
     * Calls $this->initializeFilterColumns() in case $this->filterColumns is null.
     *
     * @param   string  $table
     *
     * @return  array
     */
    public function getFilterColumns($table = null)
    {
        if ($this->filterColumns === null) {
            $this->legacyFilterColumns = false;

            $filterColumns = $this->initializeFilterColumns($table);
            $foundTables = array_intersect_key($this->getQueryColumns(), $filterColumns);
            if (empty($foundTables)) {
                $this->filterColumns[$table] = $filterColumns;
            } else {
                $this->filterColumns = $filterColumns;
            }
        } elseif ($this->legacyFilterColumns === null) {
            $foundTables = array_intersect_key($this->getQueryColumns(), $this->filterColumns);
            $this->legacyFilterColumns = empty($foundTables);
        }

        if ($this->legacyFilterColumns) {
            return $this->filterColumns;
        } elseif (! isset($this->filterColumns[$table])) {
            $this->filterColumns[$table] = $this->initializeFilterColumns($table);
        }

        return $this->filterColumns[$table];
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize
     * the filter columns lazily or dependent on a query's current base table
     *
     * @param   string  $table
     *
     * @return  array
     */
    protected function initializeFilterColumns()
    {
        // $table is not part of the signature due to PHP strict standards
        return array();
    }

    /**
     * Return the search columns being provided
     *
     * Calls $this->initializeSearchColumns() in case $this->searchColumns is null.
     *
     * @param   string  $table
     *
     * @return  array
     */
    public function getSearchColumns($table = null)
    {
        if ($this->searchColumns === null) {
            $this->legacySearchColumns = false;

            $searchColumns = $this->initializeSearchColumns($table);
            if (is_int(key($searchColumns))) {
                $this->searchColumns[$table] = $searchColumns;
            } else {
                $this->searchColumns = $searchColumns;
            }
        } elseif ($this->legacySearchColumns === null) {
            $this->legacySearchColumns = is_int(key($this->searchColumns));
        }

        if ($this->legacySearchColumns) {
            return $this->searchColumns;
        } elseif (! isset($this->searchColumns[$table])) {
            $this->searchColumns[$table] = $this->initializeSearchColumns($table);
        }

        return $this->searchColumns[$table];
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize
     * the search columns lazily or dependent on a query's current base table
     *
     * @param   string  $table
     *
     * @return  array
     */
    protected function initializeSearchColumns()
    {
        // $table is not part of the signature due to PHP strict standards
        return array();
    }

    /**
     * Return the sort rules to be applied on a query
     *
     * Calls $this->initializeSortRules() in case $this->sortRules is null.
     *
     * @param   string  $table
     *
     * @return  array
     */
    public function getSortRules($table = null)
    {
        if ($this->sortRules === null) {
            $this->legacySortRules = false;

            $sortRules = $this->initializeSortRules($table);
            $foundTables = array_intersect_key($this->getQueryColumns(), $sortRules);
            if (empty($foundTables)) {
                $this->sortRules[$table] = $sortRules;
            } else {
                $this->sortRules = $sortRules;
            }
        } elseif ($this->legacySortRules === null) {
            $foundTables = array_intersect_key($this->getQueryColumns(), $this->sortRules);
            $this->legacySortRules = empty($foundTables);
        }

        if ($this->legacySortRules) {
            return $this->sortRules;
        } elseif (! isset($this->sortRules[$table])) {
            $this->sortRules[$table] = $this->initializeSortRules($table);
        }

        return $this->sortRules[$table];
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize
     * the sort rules lazily or dependent on a query's current base table
     *
     * @param   string  $table
     *
     * @return  array
     */
    protected function initializeSortRules()
    {
        // $table is not part of the signature due to PHP strict standards
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
     * Return an array to map table names to query columns
     *
     * @return  array
     */
    protected function getColumnTableMap()
    {
        if (empty($this->columnTableMap)) {
            $this->initializeAliasMaps();
        }

        return $this->columnTableMap;
    }

    /**
     * Return a flattened array to map aliases to query columns
     *
     * @return  array
     */
    protected function getColumnAliasMap()
    {
        if (empty($this->columnAliasMap)) {
            $this->initializeAliasMaps();
        }

        return $this->columnAliasMap;
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
                    $key = $column;
                } else {
                    $key = $alias;
                    $column = preg_replace('~\n\s*~', ' ', $column);
                }

                if (array_key_exists($key, $this->aliasTableMap)) {
                    if ($this->aliasTableMap[$key] !== null) {
                        $existingTable = $this->aliasTableMap[$key];
                        $existingColumn = $this->aliasColumnMap[$key];
                        $this->aliasTableMap[$existingTable . '.' . $key] = $existingTable;
                        $this->aliasColumnMap[$existingTable . '.' . $key] = $existingColumn;
                        $this->aliasTableMap[$key] = null;
                        $this->aliasColumnMap[$key] = null;
                    }

                    $this->aliasTableMap[$table . '.' . $key] = $table;
                    $this->aliasColumnMap[$table . '.' . $key] = $column;
                } else {
                    $this->aliasTableMap[$key] = $table;
                    $this->aliasColumnMap[$key] = $column;
                }

                if (array_key_exists($column, $this->columnTableMap)) {
                    if ($this->columnTableMap[$column] !== null) {
                        $existingTable = $this->columnTableMap[$column];
                        $existingAlias = $this->columnAliasMap[$column];
                        $this->columnTableMap[$existingTable . '.' . $column] = $existingTable;
                        $this->columnAliasMap[$existingTable . '.' . $column] = $existingAlias;
                        $this->columnTableMap[$column] = null;
                        $this->columnAliasMap[$column] = null;
                    }

                    $this->columnTableMap[$table . '.' . $column] = $table;
                    $this->columnAliasMap[$table . '.' . $column] = $key;
                } else {
                    $this->columnTableMap[$column] = $table;
                    $this->columnAliasMap[$column] = $key;
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
     * Return whether this repository is capable of converting values for the given table and optional column
     *
     * @param   string  $table
     * @param   string  $column
     *
     * @return  bool
     */
    public function providesValueConversion($table, $column = null)
    {
        $conversionRules = $this->getConversionRules();
        if (empty($conversionRules)) {
            return false;
        }

        if (! isset($conversionRules[$table])) {
            return false;
        } elseif ($column === null) {
            return true;
        }

        $alias = $this->reassembleQueryColumnAlias($table, $column) ?: $column;
        return array_key_exists($alias, $conversionRules[$table]) || in_array($alias, $conversionRules[$table]);
    }

    /**
     * Convert a value supposed to be transmitted to the data source
     *
     * @param   string              $table      The table where to persist the value
     * @param   string              $name       The alias or column name
     * @param   mixed               $value      The value to convert
     * @param   RepositoryQuery     $query      An optional query to pass as context
     *                                          (Directly passed through to $this->getConverter)
     *
     * @return  mixed                           If conversion was possible, the converted value,
     *                                          otherwise the unchanged value
     */
    public function persistColumn($table, $name, $value, RepositoryQuery $query = null)
    {
        $converter = $this->getConverter($table, $name, 'persist', $query);
        if ($converter !== null) {
            $value = $this->$converter($value, $name, $table, $query);
        }

        return $value;
    }

    /**
     * Convert a value which was fetched from the data source
     *
     * @param   string              $table      The table the value has been fetched from
     * @param   string              $name       The alias or column name
     * @param   mixed               $value      The value to convert
     * @param   RepositoryQuery     $query      An optional query to pass as context
     *                                          (Directly passed through to $this->getConverter)
     *
     * @return  mixed                           If conversion was possible, the converted value,
     *                                          otherwise the unchanged value
     */
    public function retrieveColumn($table, $name, $value, RepositoryQuery $query = null)
    {
        $converter = $this->getConverter($table, $name, 'retrieve', $query);
        if ($converter !== null) {
            $value = $this->$converter($value, $name, $table, $query);
        }

        return $value;
    }

    /**
     * Return the name of the conversion method for the given alias or column name and context
     *
     * @param   string              $table      The datasource's table
     * @param   string              $name       The alias or column name for which to return a conversion method
     * @param   string              $context    The context of the conversion: persist or retrieve
     * @param   RepositoryQuery     $query      An optional query to pass as context
     *                                          (unused by the base implementation)
     *
     * @return  string
     *
     * @throws  ProgrammingError    In case a conversion rule is found but not any conversion method
     */
    protected function getConverter($table, $name, $context, RepositoryQuery $query = null)
    {
        $conversionRules = $this->getConversionRules();
        if (! isset($conversionRules[$table])) {
            return;
        }

        $tableRules = $conversionRules[$table];
        if (($alias = $this->reassembleQueryColumnAlias($table, $name)) === null) {
            $alias = $name;
        }

        // Check for a conversion method for the alias/column first
        if (array_key_exists($alias, $tableRules) || in_array($alias, $tableRules)) {
            $methodName = $context . join('', array_map('ucfirst', explode('_', $alias)));
            if (method_exists($this, $methodName)) {
                return $methodName;
            }
        }

        // The conversion method for the type is just a fallback, but it is required to exist if defined
        if (isset($tableRules[$alias])) {
            $identifier = join('', array_map('ucfirst', explode('_', $tableRules[$alias])));
            if (! method_exists($this, $context . $identifier)) {
                // Do not throw an error in case at least one conversion method exists
                if (! method_exists($this, ($context === 'persist' ? 'retrieve' : 'persist') . $identifier)) {
                    throw new ProgrammingError(
                        'Cannot find any conversion method for type "%s"'
                        . '. Add a proper conversion method or remove the type definition',
                        $tableRules[$alias]
                    );
                }

                Logger::debug(
                    'Conversion method "%s" for type definition "%s" does not exist in repository "%s".',
                    $context . $identifier,
                    $tableRules[$alias],
                    $this->getName()
                );
            } else {
                return $context . $identifier;
            }
        }
    }

    /**
     * Convert a timestamp or DateTime object to a string formatted using static::DATETIME_FORMAT
     *
     * @param   mixed   $value
     *
     * @return  string
     */
    protected function persistDateTime($value)
    {
        if (is_numeric($value)) {
            $value = date(static::DATETIME_FORMAT, $value);
        } elseif ($value instanceof DateTime) {
            $value = date(static::DATETIME_FORMAT, $value->getTimestamp()); // Using date here, to ignore any timezone
        } elseif ($value !== null) {
            throw new ProgrammingError(
                'Cannot persist value "%s" as type date_time. It\'s not a timestamp or DateTime object',
                $value
            );
        }

        return $value;
    }

    /**
     * Convert a string formatted using static::DATETIME_FORMAT to a unix timestamp
     *
     * @param   string  $value
     *
     * @return  int
     */
    protected function retrieveDateTime($value)
    {
        if (is_numeric($value)) {
            $value = (int) $value;
        } elseif (is_string($value)) {
            $dateTime = DateTime::createFromFormat(static::DATETIME_FORMAT, $value);
            if ($dateTime === false) {
                Logger::debug(
                    'Unable to parse string "%s" as type date_time with format "%s" in repository "%s"',
                    $value,
                    static::DATETIME_FORMAT,
                    $this->getName()
                );
                $value = null;
            } else {
                $value = $dateTime->getTimestamp();
            }
        } elseif ($value !== null) {
            throw new ProgrammingError(
                'Cannot retrieve value "%s" as type date_time. It\'s not a integer or (numeric) string',
                $value
            );
        }

        return $value;
    }

    /**
     * Convert the given array to an comma separated string
     *
     * @param   array|string    $value
     *
     * @return  string
     */
    protected function persistCommaSeparatedString($value)
    {
        if (is_array($value)) {
            $value = join(',', array_map('trim', $value));
        } elseif ($value !== null && !is_string($value)) {
            throw new ProgrammingError('Cannot persist value "%s" as comma separated string', $value);
        }

        return $value;
    }

    /**
     * Convert the given comma separated string to an array
     *
     * @param   string  $value
     *
     * @return  array
     */
    protected function retrieveCommaSeparatedString($value)
    {
        if ($value && is_string($value)) {
            $value = StringHelper::trimSplit($value);
        } elseif ($value !== null) {
            throw new ProgrammingError('Cannot retrieve value "%s" as array. It\'s not a string', $value);
        }

        return $value;
    }

    /**
     * Parse the given value based on the ASN.1 standard (GeneralizedTime) and return its timestamp representation
     *
     * @param   string|null     $value
     *
     * @return  int
     *
     * @see https://tools.ietf.org/html/rfc4517#section-3.3.13
     */
    protected function retrieveGeneralizedTime($value)
    {
        if ($value === null) {
            return $value;
        }

        try {
            return ASN1::parseGeneralizedTime($value)->getTimeStamp();
        } catch (InvalidArgumentException $e) {
            Logger::debug(sprintf('Repository "%s": %s', $this->getName(), $e->getMessage()));
        }
    }

    /**
     * Validate that the requested table exists and resolve it's real name if necessary
     *
     * @param   string              $table      The table to validate
     * @param   RepositoryQuery     $query      An optional query to pass as context
     *                                          (unused by the base implementation)
     *
     * @return  string                          The table's name, may differ from the given one
     *
     * @throws  ProgrammingError                In case the given table does not exist
     */
    public function requireTable($table, RepositoryQuery $query = null)
    {
        $queryColumns = $this->getQueryColumns();
        if (! isset($queryColumns[$table])) {
            throw new ProgrammingError('Table "%s" not found', $table);
        }

        $virtualTables = $this->getVirtualTables();
        if (isset($virtualTables[$table])) {
            $table = $virtualTables[$table];
        }

        return $table;
    }

    /**
     * Recurse the given filter, require each column for the given table and convert all values
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
        if ($clone) {
            $filter = clone $filter;
        }

        if ($filter->isExpression()) {
            $column = $filter->getColumn();
            $filter->setColumn($this->requireFilterColumn($table, $column, $query, $filter));
            $filter->setExpression($this->persistColumn($table, $column, $filter->getExpression(), $query));
        } elseif ($filter->isChain()) {
            foreach ($filter->filters() as $chainOrExpression) {
                $this->requireFilter($table, $chainOrExpression, $query, false);
            }
        }

        return $filter;
    }

    /**
     * Return this repository's query columns of the given table mapped to their respective aliases
     *
     * @param   string  $table
     *
     * @return  array
     *
     * @throws  ProgrammingError    In case $table does not exist
     */
    public function requireAllQueryColumns($table)
    {
        $queryColumns = $this->getQueryColumns();
        if (! array_key_exists($table, $queryColumns)) {
            throw new ProgrammingError('Table name "%s" not found', $table);
        }

        $blacklist = $this->getBlacklistedQueryColumns($table);
        $columns = array();
        foreach ($queryColumns[$table] as $alias => $column) {
            $name = is_string($alias) ? $alias : $column;
            if (! in_array($name, $blacklist)) {
                $columns[$alias] = $this->resolveQueryColumnAlias($table, $name);
            }
        }

        return $columns;
    }

    /**
     * Return the query column name for the given alias or null in case the alias does not exist
     *
     * @param   string  $table
     * @param   string  $alias
     *
     * @return  string|null
     */
    public function resolveQueryColumnAlias($table, $alias)
    {
        $aliasColumnMap = $this->getAliasColumnMap();
        if (isset($aliasColumnMap[$alias])) {
            return $aliasColumnMap[$alias];
        }

        $prefixedAlias = $table . '.' . $alias;
        if (isset($aliasColumnMap[$prefixedAlias])) {
            return $aliasColumnMap[$prefixedAlias];
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
        $columnAliasMap = $this->getColumnAliasMap();
        if (isset($columnAliasMap[$column])) {
            return $columnAliasMap[$column];
        }

        $prefixedColumn = $table . '.' . $column;
        if (isset($columnAliasMap[$prefixedColumn])) {
            return $columnAliasMap[$prefixedColumn];
        }
    }

    /**
     * Return whether the given alias or query column name is available in the given table
     *
     * @param   string  $table
     * @param   string  $alias
     *
     * @return  bool
     */
    public function validateQueryColumnAssociation($table, $alias)
    {
        $aliasTableMap = $this->getAliasTableMap();
        if (isset($aliasTableMap[$alias])) {
            return $aliasTableMap[$alias] === $table;
        }

        $prefixedAlias = $table . '.' . $alias;
        if (isset($aliasTableMap[$prefixedAlias])) {
            return true;
        }

        $columnTableMap = $this->getColumnTableMap();
        if (isset($columnTableMap[$alias])) {
            return $columnTableMap[$alias] === $table;
        }

        return isset($columnTableMap[$prefixedAlias]);
    }

    /**
     * Return whether the given column name or alias is a valid query column
     *
     * @param   string  $table  The table where to look for the column or alias
     * @param   string  $name   The column name or alias to check
     *
     * @return  bool
     */
    public function hasQueryColumn($table, $name)
    {
        if ($this->resolveQueryColumnAlias($table, $name) !== null) {
            $alias = $name;
        } elseif (($alias = $this->reassembleQueryColumnAlias($table, $name)) === null) {
            return false;
        }

        return !in_array($alias, $this->getBlacklistedQueryColumns($table))
            && $this->validateQueryColumnAssociation($table, $name);
    }

    /**
     * Validate that the given column is a valid query target and return it or the actual name if it's an alias
     *
     * @param   string              $table  The table where to look for the column or alias
     * @param   string              $name   The name or alias of the column to validate
     * @param   RepositoryQuery     $query  An optional query to pass as context (unused by the base implementation)
     *
     * @return  string                      The given column's name
     *
     * @throws  QueryException              In case the given column is not a valid query column
     */
    public function requireQueryColumn($table, $name, RepositoryQuery $query = null)
    {
        if (($column = $this->resolveQueryColumnAlias($table, $name)) !== null) {
            $alias = $name;
        } elseif (($alias = $this->reassembleQueryColumnAlias($table, $name)) !== null) {
            $column = $name;
        } else {
            throw new QueryException(t('Query column "%s" not found'), $name);
        }

        if (in_array($alias, $this->getBlacklistedQueryColumns($table))) {
            throw new QueryException(t('Column "%s" cannot be queried'), $name);
        }

        if (! $this->validateQueryColumnAssociation($table, $alias)) {
            throw new QueryException(t('Query column "%s" not found in table "%s"'), $name, $table);
        }

        return $column;
    }

    /**
     * Return whether the given column name or alias is a valid filter column
     *
     * @param   string  $table  The table where to look for the column or alias
     * @param   string  $name   The column name or alias to check
     *
     * @return  bool
     */
    public function hasFilterColumn($table, $name)
    {
        return ($this->resolveQueryColumnAlias($table, $name) !== null
            || $this->reassembleQueryColumnAlias($table, $name) !== null)
            && $this->validateQueryColumnAssociation($table, $name);
    }

    /**
     * Validate that the given column is a valid filter target and return it or the actual name if it's an alias
     *
     * @param   string              $table  The table where to look for the column or alias
     * @param   string              $name   The name or alias of the column to validate
     * @param   RepositoryQuery     $query  An optional query to pass as context (unused by the base implementation)
     * @param   FilterExpression    $filter An optional filter to pass as context (unused by the base implementation)
     *
     * @return  string                      The given column's name
     *
     * @throws  QueryException              In case the given column is not a valid filter column
     */
    public function requireFilterColumn($table, $name, RepositoryQuery $query = null, FilterExpression $filter = null)
    {
        if (($column = $this->resolveQueryColumnAlias($table, $name)) !== null) {
            $alias = $name;
        } elseif (($alias = $this->reassembleQueryColumnAlias($table, $name)) !== null) {
            $column = $name;
        } else {
            throw new QueryException(t('Filter column "%s" not found'), $name);
        }

        if (! $this->validateQueryColumnAssociation($table, $alias)) {
            throw new QueryException(t('Filter column "%s" not found in table "%s"'), $name, $table);
        }

        return $column;
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
        return $this->hasQueryColumn($table, $name);
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
        if (($column = $this->resolveQueryColumnAlias($table, $name)) !== null) {
            $alias = $name;
        } elseif (($alias = $this->reassembleQueryColumnAlias($table, $name)) !== null) {
            $column = $name;
        } else {
            throw new StatementException('Statement column "%s" not found', $name);
        }

        if (in_array($alias, $this->getBlacklistedQueryColumns($table))) {
            throw new StatementException('Column "%s" cannot be referenced in a statement', $name);
        }

        if (! $this->validateQueryColumnAssociation($table, $alias)) {
            throw new StatementException('Statement column "%s" not found in table "%s"', $name, $table);
        }

        return $column;
    }

    /**
     * Resolve the given aliases or column names of the given table supposed to be persisted and convert their values
     *
     * @param   string  $table
     * @param   array   $data
     *
     * @return  array
     */
    public function requireStatementColumns($table, array $data)
    {
        $resolved = array();
        foreach ($data as $alias => $value) {
            $resolved[$this->requireStatementColumn($table, $alias)] = $this->persistColumn($table, $alias, $value);
        }

        return $resolved;
    }
}
