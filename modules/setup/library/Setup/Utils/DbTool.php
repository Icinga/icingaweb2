<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Utils;

use PDO;
use PDOException;
use LogicException;
use Zend_Db_Adapter_Pdo_Mysql;
use Zend_Db_Adapter_Pdo_Pgsql;
use Icinga\Util\File;
use Icinga\Exception\ConfigurationError;

/**
 * Utility class to ease working with databases when setting up Icinga Web 2 or one of its modules
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
     * Whether we are connected to the database from the resource configuration
     *
     * @var bool
     */
    protected $dbFromConfig = false;

    /**
     * GRANT privilege level identifiers
     */
    const GLOBAL_LEVEL = 1;
    const PROCEDURE_LEVEL = 2;
    const DATABASE_LEVEL = 4;
    const TABLE_LEVEL = 8;
    const COLUMN_LEVEL = 16;
    const FUNCTION_LEVEL = 32;

    /**
     * All MySQL GRANT privileges with their respective level identifiers
     *
     * @var array
     */
    protected $mysqlGrantContexts = array(
        'ALL'                       => 31,
        'ALL PRIVILEGES'            => 31,
        'ALTER'                     => 13,
        'ALTER ROUTINE'             => 7,
        'CREATE'                    => 13,
        'CREATE ROUTINE'            => 5,
        'CREATE TEMPORARY TABLES'   => 5,
        'CREATE USER'               => 1,
        'CREATE VIEW'               => 13,
        'DELETE'                    => 13,
        'DROP'                      => 13,
        'EXECUTE'                   => 5, // MySQL reference states this also supports database level, 5.1.73 not though
        'FILE'                      => 1,
        'GRANT OPTION'              => 15,
        'INDEX'                     => 13,
        'INSERT'                    => 29,
        'LOCK TABLES'               => 5,
        'PROCESS'                   => 1,
        'REFERENCES'                => 12,
        'RELOAD'                    => 1,
        'REPLICATION CLIENT'        => 1,
        'REPLICATION SLAVE'         => 1,
        'SELECT'                    => 29,
        'SHOW DATABASES'            => 1,
        'SHOW VIEW'                 => 13,
        'SHUTDOWN'                  => 1,
        'SUPER'                     => 1,
        'UPDATE'                    => 29
    );

    /**
     * All PostgreSQL GRANT privileges with their respective level identifiers
     *
     * @var array
     */
    protected $pgsqlGrantContexts = array(
        'ALL'               => 63,
        'ALL PRIVILEGES'    => 63,
        'SELECT'            => 24,
        'INSERT'            => 24,
        'UPDATE'            => 24,
        'DELETE'            => 8,
        'TRUNCATE'          => 8,
        'REFERENCES'        => 24,
        'TRIGGER'           => 8,
        'CREATE'            => 12,
        'CONNECT'           => 4,
        'TEMPORARY'         => 4,
        'TEMP'              => 4,
        'EXECUTE'           => 32,
        'USAGE'             => 33,
        'CREATEROLE'        => 1
    );

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
     * @return  $this
     */
    public function connectToHost()
    {
        $this->assertHostAccess();

        if ($this->config['db'] == 'pgsql') {
            // PostgreSQL requires us to specify a database on each connection and will use
            // the current user name as default database in cases none is provided. If
            // that database doesn't exist (which might be the case here) it will error.
            // Therefore, we specify the maintenance database 'postgres' as database, which
            // is most probably present and public. (http://stackoverflow.com/q/4483139)
            $this->connect('postgres');
        } else {
            $this->connect();
        }

        return $this;
    }

    /**
     * Connect to the database
     *
     * @return  $this
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
        if (! isset($this->config['db'])) {
            throw new ConfigurationError('Can\'t connect to database server of unknown type');
        } elseif (! isset($this->config['host'])) {
            throw new ConfigurationError('Can\'t connect to database server without a hostname or address');
        } elseif (! isset($this->config['port'])) {
            throw new ConfigurationError('Can\'t connect to database server without a port');
        } elseif (! isset($this->config['username'])) {
            throw new ConfigurationError('Can\'t connect to database server without a username');
        } elseif (! isset($this->config['password'])) {
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
        if (! isset($this->config['dbname'])) {
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
     * Return whether a connection with the server has been established
     *
     * @return  bool
     */
    public function isConnected()
    {
        return $this->pdoConn !== null;
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
            $this->dbFromConfig = $dbname === $this->config['dbname'];
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
            'host'      => $this->config['host'],
            'port'      => $this->config['port'],
            'username'  => $this->config['username'],
            'password'  => $this->config['password']
        );

        if ($this->config['db'] === 'mysql') {
            if (isset($this->config['use_ssl']) && $this->config['use_ssl']) {
                $this->config['driver_options'] = array();
                # The presence of these keys as empty strings or null cause non-ssl connections to fail
                if ($this->config['ssl_key']) {
                    $config['driver_options'][PDO::MYSQL_ATTR_SSL_KEY] = $this->config['ssl_key'];
                }
                if ($this->config['ssl_cert']) {
                    $config['driver_options'][PDO::MYSQL_ATTR_SSL_CERT] = $this->config['ssl_cert'];
                }
                if ($this->config['ssl_ca']) {
                    $config['driver_options'][PDO::MYSQL_ATTR_SSL_CA] = $this->config['ssl_ca'];
                }
                if ($this->config['ssl_capath']) {
                    $config['driver_options'][PDO::MYSQL_ATTR_SSL_CAPATH] = $this->config['ssl_capath'];
                }
                if ($this->config['ssl_cipher']) {
                    $config['driver_options'][PDO::MYSQL_ATTR_SSL_CIPHER] = $this->config['ssl_cipher'];
                }
            }
            $this->zendConn = new Zend_Db_Adapter_Pdo_Mysql($config);
        } elseif ($this->config['db'] === 'pgsql') {
            $this->zendConn = new Zend_Db_Adapter_Pdo_Pgsql($config);
        } else {
            throw new ConfigurationError(
                'Failed to connect to database. Unsupported PDO driver "%s"',
                $this->config['db']
            );
        }

        $this->zendConn->getConnection(); // Force connection attempt
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

        $driverOptions = array(
            PDO::ATTR_TIMEOUT => 1,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        if (
            $this->config['db'] === 'mysql'
            && isset($this->config['use_ssl'])
            && $this->config['use_ssl']
        ) {
            # The presence of these keys as empty strings or null cause non-ssl connections to fail
            if ($this->config['ssl_key']) {
                $driverOptions[PDO::MYSQL_ATTR_SSL_KEY] = $this->config['ssl_key'];
            }
            if ($this->config['ssl_cert']) {
                $driverOptions[PDO::MYSQL_ATTR_SSL_CERT] = $this->config['ssl_cert'];
            }
            if ($this->config['ssl_ca']) {
                $driverOptions[PDO::MYSQL_ATTR_SSL_CA] = $this->config['ssl_ca'];
            }
            if ($this->config['ssl_capath']) {
                $driverOptions[PDO::MYSQL_ATTR_SSL_CAPATH] = $this->config['ssl_capath'];
            }
            if ($this->config['ssl_cipher']) {
                $driverOptions[PDO::MYSQL_ATTR_SSL_CIPHER] = $this->config['ssl_cipher'];
            }
        }

        $this->pdoConn = new PDO(
            $this->buildDsn($this->config['db'], $dbname),
            $this->config['username'],
            $this->config['password'],
            $driverOptions
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
     * Return the given table name with all wildcards being escaped
     *
     * @param   string  $tableName
     *
     * @return  string
     *
     * @throws  LogicException          In case there is no behaviour implemented for the current PDO driver
     */
    public function escapeTableWildcards($tableName)
    {
        if ($this->config['db'] === 'mysql') {
            return str_replace(array('_', '%'), array('\_', '\%'), $tableName);
        }

        throw new LogicException('Unable to escape table wildcards.');
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
        $quoted = $this->pdoConn->quote($value);
        if ($quoted === false) {
            throw new LogicException(sprintf('Unable to quote value: %s', $value));
        }

        return $quoted;
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
     * Return the version of the server currently connected to
     *
     * @return  string|null
     */
    public function getServerVersion()
    {
        if ($this->config['db'] === 'mysql') {
            return $this->query('show variables like "version"')->fetchColumn(1) ?: null;
        } elseif ($this->config['db'] === 'pgsql') {
            return $this->query('show server_version')->fetchColumn() ?: null;
        } else {
            throw new LogicException(
                sprintf('Unable to fetch the server\'s version. Unsupported PDO driver "%s"', $this->config['db'])
            );
        }
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

        foreach (preg_split('@;(?! \\\\)@', $content) as $statement) {
            if (($statement = trim($statement)) !== '') {
                $this->exec($statement);
            }
        }
    }

    /**
     * Return whether the given privileges were granted
     *
     * @param   array   $privileges     An array of strings with the required privilege names
     * @param   array   $context        An array describing the context for which the given privileges need to apply.
     *                                  Only one or more table names are currently supported
     * @param   string  $username       The login name for which to check the privileges,
     *                                  if NULL the current login is used
     *
     * @return  bool
     */
    public function checkPrivileges(array $privileges, array $context = null, $username = null)
    {
        if ($this->config['db'] === 'mysql') {
            return $this->checkMysqlPrivileges($privileges, false, $context, $username);
        } elseif ($this->config['db'] === 'pgsql') {
            return $this->checkPgsqlPrivileges($privileges, false, $context, $username);
        }
    }

    /**
     * Return whether the given privileges are grantable to other users
     *
     * @param   array   $privileges     The privileges that should be grantable
     *
     * @return  bool
     */
    public function isGrantable($privileges)
    {
        if ($this->config['db'] === 'mysql') {
            return $this->checkMysqlPrivileges($privileges, true);
        } elseif ($this->config['db'] === 'pgsql') {
            return $this->checkPgsqlPrivileges($privileges, true);
        }
    }

    /**
     * Grant all given privileges to the given user
     *
     * @param   array   $privileges     The privilege names to grant
     * @param   array   $context        An array describing the context for which the given privileges need to apply.
     *                                  Only one or more table names are currently supported
     * @param   string  $username       The username to grant the privileges to
     */
    public function grantPrivileges(array $privileges, array $context, $username)
    {
        if ($this->config['db'] === 'mysql') {
            list($_, $host) = explode('@', $this->query('select current_user()')->fetchColumn());
            $quotedDbName = $this->quoteIdentifier($this->config['dbname']);

            $grant = 'GRANT %s';
            $on = ' ON %s.%s';
            $to = sprintf(
                ' TO %s@%s',
                $this->quoteIdentifier($username),
                $this->quoteIdentifier($host)
            );

            $dbPrivileges = array();
            $tablePrivileges = array();
            foreach (array_intersect($privileges, array_keys($this->mysqlGrantContexts)) as $privilege) {
                if (! empty($context) && $this->mysqlGrantContexts[$privilege] & static::TABLE_LEVEL) {
                    $tablePrivileges[] = $privilege;
                } elseif ($this->mysqlGrantContexts[$privilege] & static::DATABASE_LEVEL) {
                    $dbPrivileges[] = $privilege;
                }
            }

            if (! empty($tablePrivileges)) {
                $tableGrant = sprintf($grant, join(',', $tablePrivileges));
                foreach ($context as $table) {
                    $this->exec($tableGrant . sprintf($on, $quotedDbName, $this->quoteIdentifier($table)) . $to);
                }
            }

            if (! empty($dbPrivileges)) {
                $this->exec(
                    sprintf($grant, join(',', $dbPrivileges))
                    . sprintf($on, $this->escapeTableWildcards($quotedDbName), '*')
                    . $to
                );
            }
        } elseif ($this->config['db'] === 'pgsql') {
            $dbPrivileges = array();
            $tablePrivileges = array();
            foreach (array_intersect($privileges, array_keys($this->pgsqlGrantContexts)) as $privilege) {
                if (! empty($context) && $this->pgsqlGrantContexts[$privilege] & static::TABLE_LEVEL) {
                    $tablePrivileges[] = $privilege;
                } elseif ($this->pgsqlGrantContexts[$privilege] & static::DATABASE_LEVEL) {
                    $dbPrivileges[] = $privilege;
                }
            }

            if (! empty($dbPrivileges)) {
                $this->exec(sprintf(
                    'GRANT %s ON DATABASE %s TO %s',
                    join(',', $dbPrivileges),
                    $this->config['dbname'],
                    $username
                ));
            }

            if (! empty($tablePrivileges)) {
                foreach ($context as $table) {
                    $this->exec(sprintf(
                        'GRANT %s ON TABLE %s TO %s',
                        join(',', $tablePrivileges),
                        $table,
                        $username
                    ));
                }
            }
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
     *
     * @return  bool
     */
    public function hasLogin($username)
    {
        if ($this->config['db'] === 'mysql') {
            $queryString = <<<EOD
SELECT 1
 FROM information_schema.user_privileges
 WHERE grantee = REPLACE(CONCAT("'", REPLACE(CURRENT_USER(), '@', "'@'"), "'"), :current, :wanted)
EOD;

            $query = $this->query(
                $queryString,
                array(
                    ':current'  => $this->config['username'],
                    ':wanted'   => $username
                )
            );
            return count($query->fetchAll()) > 0;
        } elseif ($this->config['db'] === 'pgsql') {
            $query = $this->query(
                'SELECT 1 FROM pg_catalog.pg_user WHERE usename = :ident LIMIT 1',
                array(':ident' => $username)
            );
            return count($query->fetchAll()) === 1;
        }
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
     * Check whether the current user has the given privileges
     *
     * @param   array   $privileges     The privilege names
     * @param   bool    $requireGrants  Only return true when all privileges can be granted to others
     * @param   array   $context        An array describing the context for which the given privileges need to apply.
     *                                  Only one or more table names are currently supported
     * @param   string  $username       The login name to which the passed privileges need to be granted
     *
     * @return  bool
     */
    protected function checkMysqlPrivileges(
        array $privileges,
        $requireGrants = false,
        array $context = null,
        $username = null
    ) {
        $mysqlPrivileges = array_intersect($privileges, array_keys($this->mysqlGrantContexts));
        list($_, $host) = explode('@', $this->query('select current_user()')->fetchColumn());
        $grantee = "'" . ($username === null ? $this->config['username'] : $username) . "'@'" . $host . "'";

        if (isset($this->config['dbname'])) {
            $dbPrivileges = array();
            $tablePrivileges = array();
            foreach ($mysqlPrivileges as $privilege) {
                if (! empty($context) && $this->mysqlGrantContexts[$privilege] & static::TABLE_LEVEL) {
                    $tablePrivileges[] = $privilege;
                }
                if ($this->mysqlGrantContexts[$privilege] & static::DATABASE_LEVEL) {
                    $dbPrivileges[] = $privilege;
                }
            }

            $dbPrivilegesGranted = true;
            $tablePrivilegesGranted = true;

            if (! empty($dbPrivileges)) {
                $queryString = 'SELECT COUNT(*) as matches'
                    . ' FROM information_schema.schema_privileges'
                    . ' WHERE grantee = :grantee'
                    . ' AND table_schema = :dbname'
                    . ' AND privilege_type IN (%s)'
                    . ($requireGrants ? " AND is_grantable = 'YES'" : '');

                $dbAndTableQuery = $this->query(
                    sprintf($queryString, join(',', array_map(array($this, 'quote'), $dbPrivileges))),
                    array(':grantee' => $grantee, ':dbname' => $this->escapeTableWildcards($this->config['dbname']))
                );
                $grantedDbAndTablePrivileges = (int) $dbAndTableQuery->fetchObject()->matches;
                if ($grantedDbAndTablePrivileges === count($dbPrivileges)) {
                    $tableExclusivePrivileges = array_diff($tablePrivileges, $dbPrivileges);
                    if (! empty($tableExclusivePrivileges)) {
                        $tablePrivileges = $tableExclusivePrivileges;
                        $tablePrivilegesGranted = false;
                    }
                } else {
                    $tablePrivilegesGranted = false;
                    $dbExclusivePrivileges = array_diff($dbPrivileges, $tablePrivileges);
                    if (! empty($dbExclusivePrivileges)) {
                        $dbExclusiveQuery = $this->query(
                            sprintf($queryString, join(',', array_map(array($this, 'quote'), $dbExclusivePrivileges))),
                            array(
                                ':grantee'  => $grantee,
                                ':dbname'   => $this->escapeTableWildcards($this->config['dbname'])
                            )
                        );
                        $dbPrivilegesGranted = (int) $dbExclusiveQuery->fetchObject()->matches === count(
                            $dbExclusivePrivileges
                        );
                    }
                }
            }

            if (! $tablePrivilegesGranted && !empty($tablePrivileges)) {
                $query = $this->query(
                    'SELECT COUNT(*) as matches'
                    . ' FROM information_schema.table_privileges'
                    . ' WHERE grantee = :grantee'
                    . ' AND table_schema = :dbname'
                    . ' AND table_name IN (' . join(',', array_map(array($this, 'quote'), $context)) . ')'
                    . ' AND privilege_type IN (' . join(',', array_map(array($this, 'quote'), $tablePrivileges)) . ')'
                    . ($requireGrants ? " AND is_grantable = 'YES'" : ''),
                    array(':grantee' => $grantee, ':dbname' => $this->config['dbname'])
                );
                $expectedAmountOfMatches = count($context) * count($tablePrivileges);
                $tablePrivilegesGranted = (int) $query->fetchObject()->matches === $expectedAmountOfMatches;
            }

            if ($dbPrivilegesGranted && $tablePrivilegesGranted) {
                return true;
            }
        }

        $query = $this->query(
            'SELECT COUNT(*) as matches FROM information_schema.user_privileges WHERE grantee = :grantee'
            . ' AND privilege_type IN (' . join(',', array_map(array($this, 'quote'), $mysqlPrivileges)) . ')'
            . ($requireGrants ? " AND is_grantable = 'YES'" : ''),
            array(':grantee' => $grantee)
        );
        return (int) $query->fetchObject()->matches === count($mysqlPrivileges);
    }

    /**
     * Check whether the current user has the given privileges
     *
     * Note that database and table specific privileges (i.e. not SUPER, CREATE and CREATEROLE) are ignored
     * in case no connection to the database defined in the resource configuration has been established
     *
     * @param   array   $privileges     The privilege names
     * @param   bool    $requireGrants  Only return true when all privileges can be granted to others
     * @param   array   $context        An array describing the context for which the given privileges need to apply.
     *                                  Only one or more table names are currently supported
     * @param   string  $username       The login name to which the passed privileges need to be granted
     *
     * @return  bool
     */
    public function checkPgsqlPrivileges(
        array $privileges,
        $requireGrants = false,
        array $context = null,
        $username = null
    ) {
        $privilegesGranted = true;
        if ($this->dbFromConfig) {
            $dbPrivileges = array();
            $tablePrivileges = array();
            foreach (array_intersect($privileges, array_keys($this->pgsqlGrantContexts)) as $privilege) {
                if (! empty($context) && $this->pgsqlGrantContexts[$privilege] & static::TABLE_LEVEL) {
                    $tablePrivileges[] = $privilege;
                }
                if ($this->pgsqlGrantContexts[$privilege] & static::DATABASE_LEVEL) {
                    $dbPrivileges[] = $privilege;
                }
            }

            if (! empty($dbPrivileges)) {
                $dbExclusivesGranted = true;
                foreach ($dbPrivileges as $dbPrivilege) {
                    $query = $this->query(
                        'SELECT has_database_privilege(:user, :dbname, :privilege) AS db_privilege_granted',
                        array(
                            ':user'         => $username !== null ? $username : $this->config['username'],
                            ':dbname'       => $this->config['dbname'],
                            ':privilege'    => $dbPrivilege . ($requireGrants ? ' WITH GRANT OPTION' : '')
                        )
                    );
                    if (! $query->fetchObject()->db_privilege_granted) {
                        $privilegesGranted = false;
                        if (! in_array($dbPrivilege, $tablePrivileges)) {
                            $dbExclusivesGranted = false;
                        }
                    }
                }

                if ($privilegesGranted) {
                    // Do not check privileges twice if they are already granted at database level
                    $tablePrivileges = array_diff($tablePrivileges, $dbPrivileges);
                } elseif ($dbExclusivesGranted) {
                    $privilegesGranted = true;
                }
            }

            if ($privilegesGranted && !empty($tablePrivileges)) {
                foreach (array_intersect($context, $this->listTables()) as $table) {
                    foreach ($tablePrivileges as $tablePrivilege) {
                        $query = $this->query(
                            'SELECT has_table_privilege(:user, :table, :privilege) AS table_privilege_granted',
                            array(
                                ':user'         => $username !== null ? $username : $this->config['username'],
                                ':table'        => $table,
                                ':privilege'    => $tablePrivilege . ($requireGrants ? ' WITH GRANT OPTION' : '')
                            )
                        );
                        $privilegesGranted &= $query->fetchObject()->table_privilege_granted;
                    }
                }
            }
        } else {
            // In case we cannot check whether the user got the required db-/table-privileges due to not being
            // connected to the database defined in the resource configuration it is safe to just ignore them
            // as the chances are very high that the database is created later causing the current user being
            // the owner with ALL privileges. (Which in turn can be granted to others.)

            if (array_search('CREATE', $privileges, true) !== false) {
                $query = $this->query(
                    'select rolcreatedb from pg_roles where rolname = :user',
                    array(':user' => $username !== null ? $username : $this->config['username'])
                );
                $privilegesGranted &= $query->fetchColumn() !== false;
            }
        }

        if (array_search('CREATEROLE', $privileges, true) !== false) {
            $query = $this->query(
                'select rolcreaterole from pg_roles where rolname = :user',
                array(':user' => $username !== null ? $username : $this->config['username'])
            );
            $privilegesGranted &= $query->fetchColumn() !== false;
        }

        if (array_search('SUPER', $privileges, true) !== false) {
            $query = $this->query(
                'select rolsuper from pg_roles where rolname = :user',
                array(':user' => $username !== null ? $username : $this->config['username'])
            );
            $privilegesGranted &= $query->fetchColumn() !== false;
        }

        return (bool) $privilegesGranted;
    }
}
