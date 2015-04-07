<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data\Db;

use PDO;
use Zend_Db;
use Icinga\Application\Benchmark;
use Icinga\Data\ConfigObject;
use Icinga\Data\Db\DbQuery;
use Icinga\Data\ResourceFactory;
use Icinga\Data\Selectable;
use Icinga\Exception\ConfigurationError;

/**
 * Encapsulate database connections and query creation
 */
class DbConnection implements Selectable
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
     * @return Query
     */
    public function select()
    {
        return new DbQuery($this);
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
     * Retrieve an array containing all rows of the result set
     *
     * @param   DbQuery $query
     *
     * @return  array
     */
    public function fetchAll(DbQuery $query)
    {
        Benchmark::measure('DB is fetching All');
        $result = $this->dbAdapter->fetchAll($query->getSelectQuery());
        Benchmark::measure('DB fetch done');
        return $result;
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
        Benchmark::measure('DB is fetching row');
        $result = $this->dbAdapter->fetchRow($query->getSelectQuery());
        Benchmark::measure('DB row done');
        return $result;
    }

    /**
     * Fetch a column of all rows of the result set as an array
     *
     * @param   DbQuery   $query
     * @param   int         $columnIndex Index of the column to fetch
     *
     * @return  array
     */
    public function fetchColumn(DbQuery $query, $columnIndex = 0)
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
}
