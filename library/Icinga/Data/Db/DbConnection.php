<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Db;

use PDO;
use Iterator;
use Zend_Db;
use Icinga\Data\ConfigObject;
use Icinga\Data\Db\DbQuery;
use Icinga\Data\Extensible;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterAnd;
use Icinga\Data\Filter\FilterNot;
use Icinga\Data\Filter\FilterOr;
use Icinga\Data\Reducible;
use Icinga\Data\ResourceFactory;
use Icinga\Data\Selectable;
use Icinga\Data\Updatable;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\ProgrammingError;

/**
 * Encapsulate database connections and query creation
 */
class DbConnection implements Selectable, Extensible, Updatable, Reducible
{
    /**
     * Connection config
     *
     * @var ConfigObject
     */
    private $config;

    /**
     * Database type
     *
     * @var string
     */
    private $dbType;

    /**
     * @var Zend_Db_Adapter_Abstract
     */
    private $dbAdapter;

    /**
     * Table prefix
     *
     * @var string
     */
    private $tablePrefix = '';

    private static $genericAdapterOptions = array(
        Zend_Db::AUTO_QUOTE_IDENTIFIERS => false,
        Zend_Db::CASE_FOLDING           => Zend_Db::CASE_LOWER
    );

    private static $driverOptions = array(
        PDO::ATTR_TIMEOUT    => 10,
        PDO::ATTR_CASE       => PDO::CASE_LOWER,
        PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
        // TODO: allow configurable PDO::ATTR_PERSISTENT => true
    );

    /**
     * Create a new connection object
     *
     * @param ConfigObject $config
     */
    public function __construct(ConfigObject $config = null)
    {
        $this->config = $config;
        if (isset($config->prefix)) {
            $this->tablePrefix = $config->prefix;
        }
        $this->connect();
    }

    /**
     * Provide a query on this connection
     *
     * @return  DbQuery
     */
    public function select()
    {
        return new DbQuery($this);
    }

    /**
     * Fetch and return all rows of the given query's result set using an iterator
     *
     * @param   DbQuery     $query
     *
     * @return  Iterator
     */
    public function query(DbQuery $query)
    {
        return $query->getSelectQuery()->query();
    }

    /**
     * Getter for database type
     *
     * @return string
     */
    public function getDbType()
    {
        return $this->dbType;
    }

    /**
     * Getter for the Zend_Db_Adapter
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDbAdapter()
    {
        return $this->dbAdapter;
    }

    /**
     * Create a new connection
     */
    private function connect()
    {
        $genericAdapterOptions  = self::$genericAdapterOptions;
        $driverOptions          = self::$driverOptions;
        $adapterParamaters      = array(
            'host'              => $this->config->host,
            'username'          => $this->config->username,
            'password'          => $this->config->password,
            'dbname'            => $this->config->dbname,
            'options'           => & $genericAdapterOptions,
            'driver_options'    => & $driverOptions
        );
        $this->dbType = strtolower($this->config->get('db', 'mysql'));
        switch ($this->dbType) {
            case 'mysql':
                $adapter = 'Pdo_Mysql';
                /*
                 * Set MySQL server SQL modes to behave as closely as possible to Oracle and PostgreSQL. Note that the
                 * ONLY_FULL_GROUP_BY mode is left on purpose because MySQL requires you to specify all non-aggregate
                 * columns in the group by list even if the query is grouped by the master table's primary key which is
                 * valid ANSI SQL though. Further in that case the query plan would suffer if you add more columns to
                 * the group by list.
                 */
                $driverOptions[PDO::MYSQL_ATTR_INIT_COMMAND] =
                    'SET SESSION SQL_MODE=\'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,'
                    . 'NO_AUTO_CREATE_USER,ANSI_QUOTES,PIPES_AS_CONCAT,NO_ENGINE_SUBSTITUTION\';';
                $adapterParamaters['port'] = $this->config->get('port', 3306);
                break;
            case 'pgsql':
                $adapter = 'Pdo_Pgsql';
                $adapterParamaters['port'] = $this->config->get('port', 5432);
                break;
            /*case 'oracle':
                if ($this->dbtype === 'oracle') {
                    $attributes['persistent'] = true;
                }
                $this->db = ZfDb::factory($adapter, $attributes);
                if ($adapter === 'Oracle') {
                    $this->db->setLobAsString(false);
                }
                break;*/
            default:
                throw new ConfigurationError(
                    'Backend "%s" is not supported',
                    $this->dbType
                );
        }
        $this->dbAdapter = Zend_Db::factory($adapter, $adapterParamaters);
        $this->dbAdapter->setFetchMode(Zend_Db::FETCH_OBJ);
        // TODO(el/tg): The profiler is disabled per default, why do we disable the profiler explicitly?
        $this->dbAdapter->getProfiler()->setEnabled(false);
    }

    public static function fromResourceName($name)
    {
        return new static(ResourceFactory::getResourceConfig($name));
    }

    /**
     * @deprecated Use Connection::getDbAdapter() instead
     */
    public function getConnection()
    {
        return $this->dbAdapter;
    }

    /**
     * Getter for the table prefix
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Setter for the table prefix
     *
     * @param   string $prefix
     *
     * @return  $this
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;
        return $this;
    }

    /**
     * Count all rows of the result set
     *
     * @param   DbQuery     $query
     *
     * @return  int
     */
    public function count(DbQuery $query)
    {
        return (int) $this->dbAdapter->fetchOne($query->getCountQuery());
    }

    /**
     * Retrieve an array containing all rows of the result set
     *
     * @param   DbQuery $query
     *
     * @return  array
     */
    public function fetchAll(DbQuery $query)
    {
        return $this->dbAdapter->fetchAll($query->getSelectQuery());
    }

    /**
     * Fetch the first row of the result set
     *
     * @param   DbQuery $query
     *
     * @return  mixed
     */
    public function fetchRow(DbQuery $query)
    {
        return $this->dbAdapter->fetchRow($query->getSelectQuery());
    }

    /**
     * Fetch the first column of all rows of the result set as an array
     *
     * @param   DbQuery   $query
     *
     * @return  array
     */
    public function fetchColumn(DbQuery $query)
    {
        return $this->dbAdapter->fetchCol($query->getSelectQuery());
    }

    /**
     * Fetch the first column of the first row of the result set
     *
     * @param   DbQuery $query
     *
     * @return  string
     */
    public function fetchOne(DbQuery $query)
    {
        return $this->dbAdapter->fetchOne($query->getSelectQuery());
    }

    /**
     * Fetch all rows of the result set as an array of key-value pairs
     *
     * The first column is the key, the second column is the value.
     *
     * @param   DbQuery $query
     *
     * @return  array
     */
    public function fetchPairs(DbQuery $query)
    {
        return $this->dbAdapter->fetchPairs($query->getSelectQuery());
    }

    /**
     * Insert a table row with the given data
     *
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
        $values = array();
        foreach ($bind as $column => $_) {
            $values[] = ':' . $column;
        }

        $sql = 'INSERT INTO ' . $table
            . ' (' . join(', ', array_keys($bind)) . ') '
            . 'VALUES (' . join(', ', $values) . ')';
        $statement = $this->dbAdapter->prepare($sql);

        foreach ($bind as $column => $value) {
            $type = isset($types[$column]) ? $types[$column] : PDO::PARAM_STR;
            $statement->bindValue(':' . $column, $value, $type);
        }

        $statement->execute();
        return $statement->rowCount();
    }

    /**
     * Update table rows with the given data, optionally limited by using a filter
     *
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
        $set = array();
        foreach ($bind as $column => $_) {
            $set[] = $column . ' = :' . $column;
        }

        $sql = 'UPDATE ' . $table
            . ' SET ' . join(', ', $set)
            . ($filter ? ' WHERE ' . $this->renderFilter($filter) : '');
        $statement = $this->dbAdapter->prepare($sql);

        foreach ($bind as $column => $value) {
            $type = isset($types[$column]) ? $types[$column] : PDO::PARAM_STR;
            $statement->bindValue(':' . $column, $value, $type);
        }

        $statement->execute();
        return $statement->rowCount();
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
        return $this->dbAdapter->delete($table, $filter ? $this->renderFilter($filter) : '');
    }

    /**
     * Render and return the given filter as SQL-WHERE clause
     *
     * @param   Filter  $filter
     *
     * @return  string
     */
    public function renderFilter(Filter $filter, $level = 0)
    {
        // TODO: This is supposed to supersede DbQuery::renderFilter()
        $where = '';
        if ($filter->isChain()) {
            if ($filter instanceof FilterAnd) {
                $operator = ' AND ';
            } elseif ($filter instanceof FilterOr) {
                $operator = ' OR ';
            } elseif ($filter instanceof FilterNot) {
                $operator = ' AND ';
                $where .= ' NOT ';
            } else {
                throw new ProgrammingError('Cannot render filter: %s', get_class($filter));
            }

            if (! $filter->isEmpty()) {
                $parts = array();
                foreach ($filter->filters() as $filterPart) {
                    $part = $this->renderFilter($filterPart, $level + 1);
                    if ($part) {
                        $parts[] = $part;
                    }
                }

                if (! empty($parts)) {
                    if ($level > 0) {
                        $where .= ' (' . implode($operator, $parts) . ') ';
                    } else {
                        $where .= implode($operator, $parts);
                    }
                }
            } else {
                return ''; // Explicitly return the empty string due to the FilterNot case
            }
        } else {
            $where .= $this->renderFilterExpression($filter);
        }

        return $where;
    }

    /**
     * Render and return the given filter expression
     *
     * @param   Filter  $filter
     *
     * @return  string
     */
    protected function renderFilterExpression(Filter $filter)
    {
        $column = $filter->getColumn();
        $sign = $filter->getSign();
        $value = $filter->getExpression();

        if (is_array($value) && $sign === '=') {
            // TODO: Should we support this? Doesn't work for blub*
            return $column . ' IN (' . $this->dbAdapter->quote($value) . ')';
        } elseif ($sign === '=' && strpos($value, '*') !== false) {
            return $column . ' LIKE ' . $this->dbAdapter->quote(preg_replace('~\*~', '%', $value));
        } elseif ($sign === '!=' && strpos($value, '*') !== false) {
            return $column . ' NOT LIKE ' . $this->dbAdapter->quote(preg_replace('~\*~', '%', $value));
        } else {
            return $column . ' ' . $sign . ' ' . $this->dbAdapter->quote($value);
        }
    }
}
