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

        if ($this->config['db'] == 'pgsql') {
            // PostgreSQL requires us to specify a database on each connection and will use
            // the current user name as default database in cases none is provided. If
            // that database doesn't exist (which might be the case here) it will error.
            // Therefore, we specify the maintenance database 'postgres' as database, which
            // is most probably present and public.
            $this->connect('postgres');
        } else {
            $this->connect();
        }
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
     * Return the given identifier escaped with backticks
     *
     * @param   string  $identifier     The identifier to escape
     *
     * @return  string
     *
     * @throws  LogicException          In case there is no behaviour implemented for the current PDO driver
     */
    public function quoteIdentifier($identifier)
    {
        if ($this->config['db'] === 'mysql') {
            return '`' . str_replace('`', '``', $identifier) . '`';
        } elseif ($this->config['db'] === 'pgsql') {
            return '"' . str_replace('"', '""', $identifier) . '"';
        } else {
            throw new LogicException('Unable to quote identifier.');
        }
    }

    /**
     * Return the given value escaped as string
     *
     * @param   mixed  $value       The value to escape
     *
     * @return  string
     *
     * @throws  LogicException      In case there is no behaviour implemented for the current PDO driver
     */
    public function quote($value)
    {
        $value = $this->pdoConn->quote($value);
        if ($value === false) {
            throw new LogicException('Unable to quote value');
        }

        return $value;
    }

    /**
     * Execute a SQL statement and return the affected row count
     *
     * Use $params to use a prepared statement.
     *
     * @param   string  $statement  The statement to execute
     * @param   array   $params     The params to bind
     *
     * @return  int
     */
    public function exec($statement, $params = array())
    {
        if (empty($params)) {
            return $this->pdoConn->exec($statement);
        }

        $stmt = $this->pdoConn->prepare($statement);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Execute a SQL statement and return the result
     *
     * Use $params to use a prepared statement.
     *
     * @param   string  $statement  The statement to execute
     * @param   array   $params     The params to bind
     *
     * @return  mixed
     */
    public function query($statement, $params = array())
    {
        if ($this->zendConn !== null) {
            return $this->zendConn->query($statement, $params);
        }

        if (empty($params)) {
            return $this->pdoConn->query($statement);
        }

        $stmt = $this->pdoConn->prepare($statement);
        $stmt->execute($params);
        return $stmt;
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
    public function checkPrivileges(array $privileges, $table = null)
    {
        if ($this->config['db'] === 'mysql') {
            return $this->checkMysqlPriv($privileges);
        } else {
            return $this->checkPgsqlPriv($privileges, $table);
        }
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
     * @param   string  $password   The password for user $username, required in case it's a MySQL database
     *
     * @return  bool
     */
    public function hasLogin($username, $password = null)
    {
        if ($this->config['db'] === 'mysql') {
            // probe login by trial and error since we don't know our host name or it may be globbed
            try {
                $probeConf = $this->config;
                $probeConf['username'] = $username;
                $probeConf['password'] = $password;
                $probe = new DbTool($probeConf);
                $probe->connectToHost();
            } catch (PDOException $e) {
                return false;
            }

            return true;
        } elseif ($this->config['db'] === 'pgsql') {
            $rowCount = $this->exec(
                'SELECT usename FROM pg_catalog.pg_user WHERE usename = :ident LIMIT 1',
                array(':ident' => $username)
            );
        }

        return $rowCount === 1;
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
            list($_, $host) = explode('@', $this->query('select current_user()')->fetchColumn());
            $this->exec(
                'CREATE USER :user@:host IDENTIFIED BY :passw',
                array(':user' => $username, ':host' => $host, ':passw' => $password)
            );
        } elseif ($this->config['db'] === 'pgsql') {
            $this->exec(sprintf(
                'CREATE USER %s WITH PASSWORD %s',
                $this->quoteIdentifier($username),
                $this->quote($password)
            ));
        }
    }

    /**
     * Check whether the current role has GRANT permissions
     *
     * @param array $privileges
     * @param       $database
     */
    public function checkMysqlGrantOption(array $privileges)
    {
        return $this->checkMysqlPriv($privileges, true);
    }

    /**
     * Check whether the current user has the given global privileges
     *
     * @param array     $privileges     The privilege names
     * @param boolean   $requireGrants  Only return true when all privileges can be granted to others
     *
     * @return bool
     */
    public function checkMysqlPriv(array $privileges, $requireGrants = false)
    {
        $cnt = count($privileges);
        if ($cnt <= 0) {
            return true;
        }
        $grantOption = '';
        if ($requireGrants) {
            $grantOption = ' AND IS_GRANTABLE = \'YES\'';
        }
        $rows = $this->exec(
            'SELECT PRIVILEGE_TYPE FROM information_schema.user_privileges ' .
            ' WHERE GRANTEE = CONCAT("\'", REPLACE(CURRENT_USER(), \'@\', "\'@\'"), "\'") ' .
            ' AND PRIVILEGE_TYPE IN (?' . str_repeat(',?', $cnt - 1) . ') ' . $grantOption . ';',
            $privileges
        );

        return $cnt === $rows;
    }

    /**
     * Check whether the current role has GRANT permissions for the given database name
     *
     * For Postgres, this will be assumed as true when:
     * <ul>
     *  <li>The role can create new databases and the database does <b>not</b> yet exist </li>
     *  <li>The database exists but the current role is the owner of it</li>
     *  <li>The database exists but the role has superuser permissions</li>
     *  <li>The role does not own the database, but has the necessary grants on it</li>
     * </ul>
     * A more fine-grained check of schema, table and columns permissions in the database
     * will not happen.
     *
     * @param   array   $privileges
     * @param           $database   The database
     * @param           $table      The optional table
     *
     * @return bool
     */
    public function checkPgsqlGrantOption(array $privileges, $database, $table = null)
    {
        if ($this->checkPgsqlPriv(array('SUPER'))) {
            // superuser
            return true;
        }
        $create = $this->checkPgsqlPriv(array('CREATE', 'CREATE USER'));
        $owner = $this->query(sprintf(
            'SELECT pg_catalog.pg_get_userbyid(datdba) FROM pg_database WHERE datname = %s',
            $this->quote($database)
        ))->fetchColumn();
        if ($owner !== false) {
            if ($owner !== $this->config['username']) {
                // database already exists and the user is not owner of the database
                return $this->checkPgsqlPriv($privileges, $table, true);
            } else {
                // database already exists and the user is owner of the database
                return true;
            }
        }
        // database does not exist, permission depends on createdb and createrole permissions
        return $create;
    }

    /**
     * Check whether the current role has the given privileges
     *
     * NOTE: The only global role privileges in Postgres are SUPER (superuser), CREATE and CREATE USER
     * (databases and roles), all others will be ignored in case no database was given
     *
     * @param array     $privileges     The privileges to check
     * @param           $table          The optional schema to use, defaults to 'public'
     * @param           $withGrant      Whether we also require the grant option on the given privileges
     *
     * @return bool
     */
    public function checkPgsqlPriv(array $privileges, $table = null, $withGrantOption = false)
    {
        if (isset($table)) {
            $queries = array();
            foreach ($privileges as $privilege) {
                if (false === array_search($privilege, array('CREATE USER', 'CREATE', 'SUPER'))) {
                    $queries[] = sprintf (
                            'has_table_privilege(%s, %s)',
                            $this->quote($table),
                            $this->quote($privilege . ($withGrantOption ? ' WITH GRANT OPTION' : ''))
                        ) . ' AS ' . $this->quoteIdentifier($privilege);
                }
            }
            $ret = $this->query('SELECT ' . join (', ', $queries) . ';')->fetch();
            if (false === $ret || false !== array_search(false, $ret)) {
                return false;
            }
        }
        if (false !== array_search('CREATE USER', $privileges)) {
            $query = $this->query('select rolcreaterole from pg_roles where rolname = current_user;');
            $createrole = $query->fetchColumn();
            if (false === $createrole) {
                return false;
            }
        }

        if (false !== array_search('CREATE', $privileges)) {
            $query = $this->query('select rolcreatedb from pg_roles where rolname = current_user;');
            $createdb = $query->fetchColumn();
            if (false === $createdb) {
                return false;
            }
        }
        if (false !== array_search('SUPER', $privileges)) {
            $query = $this->query('select rolsuper from pg_roles where rolname = current_user;');
            $super = $query->fetchColumn();
            if (false === $super) {
                return false;
            }
        }
        return true;
    }
}
