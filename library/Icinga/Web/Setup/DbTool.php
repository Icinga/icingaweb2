<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Setup;

use PDO;
use PDOException;
use LogicException;
use Zend_Db_Adapter_Pdo_Mysql;
use Zend_Db_Adapter_Pdo_Pgsql;
use Icinga\Util\File;
use Icinga\Application\Platform;
use Icinga\Exception\ConfigurationError;

/**
 * Utility class to ease working with databases when installing Icinga Web 2 or one of its modules
 */
class DbTool
{
    /**
     * The PDO database connection
     *
     * @var PDO
     */
    protected $pdoConn;

    /**
     * The Zend database adapter
     *
     * @var Zend_Db_Adapter_Pdo_Abstract
     */
    protected $zendConn;

    /**
     * The resource configuration
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new DbTool
     *
     * @param   array   $config     The resource configuration to use
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Connect to the server
     *
     * @return  self
     */
    public function connectToHost()
    {
        $this->assertHostAccess();
        $this->connect();
        return $this;
    }

    /**
     * Connect to the database
     *
     * @return  self
     */
    public function connectToDb()
    {
        $this->assertHostAccess();
        $this->assertDatabaseAccess();
        $this->connect($this->config['dbname']);
        return $this;
    }

    /**
     * Assert that all configuration values exist that are required to connect to a server
     *
     * @throws  ConfigurationError
     */
    protected function assertHostAccess()
    {
        if (false === isset($this->config['db'])) {
            throw new ConfigurationError('Can\'t connect to database server of unknown type');
        } elseif (false === isset($this->config['host'])) {
            throw new ConfigurationError('Can\'t connect to database server without a hostname or address');
        } elseif (false === isset($this->config['port'])) {
            throw new ConfigurationError('Can\'t connect to database server without a port');
        } elseif (false === isset($this->config['username'])) {
            throw new ConfigurationError('Can\'t connect to database server without a username');
        } elseif (false === isset($this->config['password'])) {
            throw new ConfigurationError('Can\'t connect to database server without a password');
        }
    }

    /**
     * Assert that all configuration values exist that are required to connect to a database
     *
     * @throws  ConfigurationError
     */
    protected function assertDatabaseAccess()
    {
        if (false === isset($this->config['dbname'])) {
            throw new ConfigurationError('Can\'t connect to database without a valid database name');
        }
    }

    /**
     * Assert that a connection with a database has been established
     *
     * @throws  LogicException
     */
    protected function assertConnectedToDb()
    {
        if ($this->zendConn === null) {
            throw new LogicException('Not connected to database');
        }
    }

    /**
     * Establish a connection with the database or just the server by omitting the database name
     *
     * @param   string  $dbname     The name of the database to connect to
     */
    public function connect($dbname = null)
    {
        $this->_pdoConnect($dbname);
        if ($dbname !== null) {
            $this->_zendConnect($dbname);
        }
    }

    /**
     * Reestablish a connection with the database or just the server by omitting the database name
     *
     * @param   string  $dbname     The name of the database to connect to
     */
    public function reconnect($dbname = null)
    {
        $this->pdoConn = null;
        $this->zendConn = null;
        $this->connect($dbname);
    }

    /**
     * Initialize Zend database adapter
     *
     * @param   string  $dbname     The name of the database to connect with
     *
     * @throws  ConfigurationError  In case the resource type is not a supported PDO driver name
     */
    protected function _zendConnect($dbname)
    {
        if ($this->zendConn !== null) {
            return;
        }

        $config = array(
            'dbname'    => $dbname,
            'username'  => $this->config['username'],
            'password'  => $this->config['password']
        );

        if ($this->config['db'] === 'mysql') {
            $this->zendConn = new Zend_Db_Adapter_Pdo_Mysql($config);
        } elseif ($this->config['db'] === 'pgsql') {
            $this->zendConn = new Zend_Db_Adapter_Pdo_Pgsql($config);
        } else {
            throw new ConfigurationError(
                'Failed to connect to database. Unsupported PDO driver "%s"',
                $this->config['db']
            );
        }
    }

    /**
     * Initialize PDO connection
     *
     * @param   string  $dbname     The name of the database to connect with
     */
    protected function _pdoConnect($dbname)
    {
        if ($this->pdoConn !== null) {
            return;
        }

        $this->pdoConn = new PDO(
            $this->buildDsn($this->config['db'], $dbname),
            $this->config['username'],
            $this->config['password'],
            array(PDO::ATTR_TIMEOUT => 1, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
    }

    /**
     * Return a datasource name for the given database type and name
     *
     * @param   string      $dbtype
     * @param   string      $dbname
     *
     * @return  string
     *
     * @throws  ConfigurationError      In case the passed database type is not supported
     */
    protected function buildDsn($dbtype, $dbname = null)
    {
        if ($dbtype === 'mysql') {
            return 'mysql:host=' . $this->config['host'] . ';port=' . $this->config['port']
                . ($dbname !== null ? ';dbname=' . $dbname : '');
        } elseif ($dbtype === 'pgsql') {
            return 'pgsql:host=' . $this->config['host'] . ';port=' . $this->config['port']
                . ($dbname !== null ? ';dbname=' . $dbname : '');
        } else {
            throw new ConfigurationError(
                'Failed to build data source name. Unsupported PDO driver "%s"',
                $dbtype
            );
        }
    }

    /**
     * Try to connect to the server and throw an exception if this fails
     *
     * @throws  PDOException    In case an error occurs that does not indicate that authentication failed
     */
    public function checkConnectivity()
    {
        try {
            $this->connectToHost();
        } catch (PDOException $e) {
            if ($this->config['db'] === 'mysql') {
                $code = $e->getCode();
                if ($code !== 1040 && $code !== 1045) {
                    throw $e;
                }
            } elseif ($this->config['db'] === 'pgsql') {
                if (strpos($e->getMessage(), $this->config['username']) === false) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Execute a SQL statement and return the affected row count
     *
     * @param   string  $statement  The statement to execute
     *
     * @return  int
     */
    public function exec($statement)
    {
        return $this->pdoConn->exec($statement);
    }

    /**
     * Import the given SQL file
     *
     * @param   string  $filepath   The file to import
     */
    public function import($filepath)
    {
        $file = new File($filepath);
        $content = join(PHP_EOL, iterator_to_array($file)); // There is no fread() before PHP 5.5 :(

        foreach (explode(';', $content) as $statement) {
            if (($statement = trim($statement)) !== '') {
                $this->exec($statement);
            }
        }
    }

    /**
     * Return whether the given privileges were granted
     *
     * @param   array   $privileges     An array of strings with the required privilege names
     *
     * @return  bool
     */
    public function checkPrivileges(array $privileges)
    {
        return true; // TODO(7163): Implement privilege checks
    }

    /**
     * Return a list of all existing database tables
     *
     * @return  array
     */
    public function listTables()
    {
        $this->assertConnectedToDb();
        return $this->zendConn->listTables();
    }

    /**
     * Return whether the given database login exists
     *
     * @param   string  $username   The username to search
     *
     * @return  bool
     */
    public function hasLogin($username)
    {
        if ($this->config['db'] === 'mysql') {
            $stmt = $this->pdoConn->prepare(
                'SELECT grantee FROM information_schema.user_privileges WHERE grantee = :ident LIMIT 1'
            );
            $stmt->execute(array(':ident' => "'" . $username . "'@'" . Platform::getFqdn() . "'"));
            return $stmt->rowCount() === 1;
        } elseif ($this->config['db'] === 'pgsql') {
            $stmt = $this->pdoConn->prepare(
                'SELECT usename FROM pg_catalog.pg_user WHERE usename = :ident LIMIT 1'
            );
            $stmt->execute(array(':ident' => $username));
            return $stmt->rowCount() === 1;
        }

        return false;
    }

    /**
     * Add a new database login
     *
     * @param   string  $username   The username of the new login
     * @param   string  $password   The password of the new login
     */
    public function addLogin($username, $password)
    {
        if ($this->config['db'] === 'mysql') {
            $stmt = $this->pdoConn->prepare('CREATE USER :user@:host IDENTIFIED BY :passw');
            $stmt->execute(array(':user' => $username, ':host' => Platform::getFqdn(), ':passw' => $password));
        } elseif ($this->config['db'] === 'pgsql') {
            $this->pdoConn->exec("CREATE USER $username WITH PASSWORD '$password'");
        }
    }
}
