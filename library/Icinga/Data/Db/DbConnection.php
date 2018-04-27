<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Db;

use Exception;
use Icinga\Data\Inspectable;
use Icinga\Data\Inspection;
use PDO;
use Iterator;
use Zend_Db;
use Zend_Db_Expr;
use Icinga\Data\ConfigObject;
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
class DbConnection implements Selectable, Extensible, Updatable, Reducible, Inspectable
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
     * @var \Zend_Db_Adapter_Abstract
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
        PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION
    );

    /**
     * Create a new connection object
     *
     * @param ConfigObject $config
     */
    public function __construct(ConfigObject $config = null)
    {
        $this->config = $config;
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
     * Get the connection configuration
     *
     * @return  ConfigObject
     */
    public function getConfig()
    {
        return $this->config;
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
     * @return \Zend_Db_Adapter_Abstract
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
            'charset'           => $this->config->charset ?: null,
            'persistent'        => (bool) $this->config->get('persistent', false),
            'options'           => & $genericAdapterOptions,
            'driver_options'    => & $driverOptions
        );
        $this->dbType = strtolower($this->config->get('db', 'mysql'));
        switch ($this->dbType) {
            case 'mssql':
                $adapter = 'Pdo_Mssql';
                $pdoType = $this->config->get('pdoType');
                if (empty($pdoType)) {
                    if (extension_loaded('sqlsrv')) {
                        $adapter = 'Sqlsrv';
                    } else {
                        $pdoType = 'dblib';
                    }
                }
                if ($pdoType === 'dblib') {
                    // Driver does not support setting attributes
                    unset($adapterParamaters['persistent']);
                    unset($adapterParamaters['options']);
                    unset($adapterParamaters['driver_options']);
                }
                if (! empty($pdoType)) {
                    $adapterParamaters['pdoType'] = $pdoType;
                }
                $defaultPort = 1433;
                break;
            case 'mysql':
                $adapter = 'Pdo_Mysql';
                if ($this->config->use_ssl) {
                    # The presence of these keys as empty strings or null cause non-ssl connections to fail
                    if ($this->config->ssl_key) {
                        $adapterParamaters['driver_options'][PDO::MYSQL_ATTR_SSL_KEY] = $this->config->ssl_key;
                    }
                    if ($this->config->ssl_cert) {
                        $adapterParamaters['driver_options'][PDO::MYSQL_ATTR_SSL_CERT] = $this->config->ssl_cert;
                    }
                    if ($this->config->ssl_ca) {
                        $adapterParamaters['driver_options'][PDO::MYSQL_ATTR_SSL_CA] = $this->config->ssl_ca;
                    }
                    if ($this->config->ssl_capath) {
                        $adapterParamaters['driver_options'][PDO::MYSQL_ATTR_SSL_CAPATH] = $this->config->ssl_capath;
                    }
                    if ($this->config->ssl_cipher) {
                        $adapterParamaters['driver_options'][PDO::MYSQL_ATTR_SSL_CIPHER] = $this->config->ssl_cipher;
                    }
                }
                /*
                 * Set MySQL server SQL modes to behave as closely as possible to Oracle and PostgreSQL. Note that the
                 * ONLY_FULL_GROUP_BY mode is left on purpose because MySQL requires you to specify all non-aggregate
                 * columns in the group by list even if the query is grouped by the master table's primary key which is
                 * valid ANSI SQL though. Further in that case the query plan would suffer if you add more columns to
                 * the group by list.
                 */
                $driverOptions[PDO::MYSQL_ATTR_INIT_COMMAND] =
                    'SET SESSION SQL_MODE=\'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,'
                    . 'NO_AUTO_CREATE_USER,ANSI_QUOTES,PIPES_AS_CONCAT,NO_ENGINE_SUBSTITUTION\'';
                if (isset($adapterParamaters['charset'])) {
                    $driverOptions[PDO::MYSQL_ATTR_INIT_COMMAND] .= ', NAMES ' . $adapterParamaters['charset'];
                    unset($adapterParamaters['charset']);
                }
                $driverOptions[PDO::MYSQL_ATTR_INIT_COMMAND] .=';';
                $defaultPort = 3306;
                break;
            case 'oci':
                $adapter = 'Oracle';
                unset($adapterParamaters['options']);
                unset($adapterParamaters['driver_options']);
                $adapterParamaters['driver_options'] = array(
                    'lob_as_string' => true
                );
                $defaultPort = 1521;
                break;
            case 'oracle':
                $adapter = 'Pdo_Oci';
                $defaultPort = 1521;
                break;
            case 'pgsql':
                $adapter = 'Pdo_Pgsql';
                $defaultPort = 5432;
                break;
            case 'ibm':
                $adapter = 'Pdo_Ibm';
                $defaultPort = 50000;
                break;
            default:
                throw new ConfigurationError(
                    'Backend "%s" is not supported',
                    $this->dbType
                );
        }
        $adapterParamaters['port'] = $this->config->get('port', $defaultPort);
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
        $columns = $values = array();
        foreach ($bind as $column => $value) {
            $columns[] = $column;
            if ($value instanceof Zend_Db_Expr) {
                $values[] = (string) $value;
                unset($bind[$column]);
            } else {
                $values[] = ':' . $column;
            }
        }

        $sql = 'INSERT INTO ' . $table
            . ' (' . join(', ', $columns) . ') '
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
        $set = array();
        foreach ($bind as $column => $value) {
            if ($value instanceof Zend_Db_Expr) {
                $set[] = $column . ' = ' . $value;
                unset($bind[$column]);
            } else {
                $set[] = $column . ' = :' . $column;
            }
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

        if (is_array($value)) {
            if ($sign === '=') {
                return $column . ' IN (' . $this->dbAdapter->quote($value) . ')';
            } elseif ($sign === '!=') {
                return sprintf('(%1$s NOT IN (%2$s) OR %1$s IS NULL)', $column, $this->dbAdapter->quote($value));
            }

            throw new ProgrammingError(
                'Unable to render array expressions with operators other than equal or not equal'
            );
        } elseif ($sign === '=' && strpos($value, '*') !== false) {
            if ($value === '*') {
                // We'll ignore such filters as it prevents index usage and because "*" means anything, so whether we're
                // using a real column with a valid comparison here or just an expression which can only be evaluated to
                // true makes no difference, except for performance reasons...
                return new Zend_Db_Expr('TRUE');
            }

            return $column . ' LIKE ' . $this->dbAdapter->quote(preg_replace('~\*~', '%', $value));
        } elseif ($sign === '!=' && strpos($value, '*') !== false) {
            if ($value === '*') {
                // We'll ignore such filters as it prevents index usage and because "*" means nothing, so whether we're
                // using a real column with a valid comparison here or just an expression which cannot be evaluated to
                // true makes no difference, except for performance reasons...
                return new Zend_Db_Expr('FALSE');
            }

            return sprintf(
                '(%1$s NOT LIKE %2$s OR %1$s IS NULL)',
                $column,
                $this->dbAdapter->quote(preg_replace('~\*~', '%', $value))
            );
        } elseif ($sign === '!=') {
            return sprintf('(%1$s != %2$s OR %1$s IS NULL)', $column, $this->dbAdapter->quote($value));
        } else {
            return sprintf('%s %s %s', $column, $sign, $this->dbAdapter->quote($value));
        }
    }

    public function inspect()
    {
        $insp = new Inspection('Db Connection');
        try {
            $this->getDbAdapter()->getConnection();
            $config = $this->dbAdapter->getConfig();
            $insp->write(sprintf(
                'Connection to %s as %s on %s:%s successful',
                $config['dbname'],
                $config['username'],
                $config['host'],
                $config['port']
            ));
            switch ($this->dbType) {
                case 'mysql':
                    $rows = $this->dbAdapter->query(
                        'SHOW VARIABLES WHERE variable_name ' .
                        'IN (\'version\', \'protocol_version\', \'version_compile_os\', \'have_ssl\');'
                    )->fetchAll();
                    $sqlinsp = new Inspection('MySQL');
                    $hasSsl = false;
                    foreach ($rows as $row) {
                        $sqlinsp->write($row->variable_name . ': ' . $row->value);
                        if ($row->variable_name === 'have_ssl' && $row->value === 'YES') {
                            $hasSsl = true;
                        }
                    }
                    if ($hasSsl) {
                        $ssl_rows = $this->dbAdapter->query(
                            'SHOW STATUS WHERE variable_name ' .
                            'IN (\'Ssl_Cipher\');'
                        )->fetchAll();
                        foreach ($ssl_rows as $ssl_row) {
                            $sqlinsp->write($ssl_row->variable_name . ': ' . $ssl_row->value);
                        }
                    }
                    $insp->write($sqlinsp);
                    break;
                case 'pgsql':
                    $row = $this->dbAdapter->query('SELECT version();')->fetchAll();
                    $sqlinsp = new Inspection('PostgreSQL');
                    $sqlinsp->write($row[0]->version);
                    $insp->write($sqlinsp);
                    break;
            }
        } catch (Exception $e) {
            return $insp->error(sprintf('Connection failed %s', $e->getMessage()));
        }
        return $insp;
    }
}
