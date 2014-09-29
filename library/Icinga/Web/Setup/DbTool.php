<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Setup;

use PDO;
use PDOException;
use Icinga\Exception\ConfigurationError;

/**
 * Utility class to ease working with databases when installing Icinga Web 2 or one of its modules
 */
class DbTool
{
    /**
     * The database connection
     *
     * @var PDO
     */
    protected $conn;

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
     */
    public function connectToHost()
    {
        $this->assertHostAccess();
        $this->connect();
    }

    /**
     * Connect to the database
     */
    public function connectToDb()
    {
        $this->assertHostAccess();
        $this->assertDatabaseAccess();
        $this->connect($this->config['dbname']);
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
     * Establish a connection with the database or just the server by omitting the database name
     *
     * @param   string  $dbname     The name of the database to connect to
     */
    public function connect($dbname = null)
    {
        if ($this->conn !== null) {
            return;
        }

        $this->conn = new PDO(
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
}
