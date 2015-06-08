<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Livestatus;

use Icinga\Application\Benchmark;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\SystemPermissionException;
use Icinga\Exception\IcingaException;
use Exception;
use SplFixedArray;

/**
 * Backend class managing handling MKI Livestatus connections
 *
 * Usage example:
 *
 * <code>
 * $lconf = new Connection((object) array(
 *     'hostname' => 'localhost',
 *     'root_dn'  => 'dc=monitoring,dc=...',
 *     'bind_dn'  => 'cn=Mangager,dc=monitoring,dc=...',
 *     'bind_pw'  => '***'
 * ));
 * </code>
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Connection
{
    const TYPE_UNIX = 1;
    const TYPE_TCP  = 2;

    const FIELD_SEPARATOR = '`';

    protected $bytesRead = 0;
    protected $responseSize;
    protected $status;
    protected $headers;

    // List of available Livestatus tables. Kept here as we otherwise get no
    // useful error message
    protected $available_tables = array(
        'hosts',               // hosts
        'services',            // services, joined with all data from hosts
        'hostgroups',          // hostgroups
        'servicegroups',       // servicegroups
        'contactgroups',       // contact groups
        'servicesbygroup',     // all services grouped by service groups
        'servicesbyhostgroup', // all services grouped by host groups
        'hostsbygroup',        // all hosts grouped by host groups
        'contacts',            // contacts
        'commands',            // defined commands
        'timeperiods',         // time period definitions (currently only name
                               // and alias)
        'downtimes',           // all scheduled host and service downtimes,
                               // joined with data from hosts and services.
        'comments',            // all host and service comments
        'log',                 // a transparent access to the nagios logfiles
                               // (include archived ones)ones
        'status',              // general performance and status information.
                               // This table contains exactly one dataset.
        'columns',             // a complete list of all tables and columns
                               // available via Livestatus, including
                               // descriptions!
        'statehist',           // 1.2.1i2 sla statistics for hosts and services,
                               // joined with data from hosts, services and log.
    );

    protected $socket_path;
    protected $socket_host;
    protected $socket_port;
    protected $socket_type;
    protected $connection;

    /**
     * Whether the given table name is valid
     *
     * @param string $name table name
     *
     * @return bool
     */
    public function hasTable($name)
    {
        return in_array($name, $this->available_tables);
    }

    public function __construct($socket = '/var/lib/icinga/rw/live')
    {
        $this->assertPhpExtensionLoaded('sockets');
        if ($socket[0] === '/') {
            if (! is_writable($socket)) {
                throw new SystemPermissionException(
                    'Cannot write to livestatus socket "%s"',
                    $socket
                );
            }
            $this->socket_type = self::TYPE_UNIX;
            $this->socket_path = $socket;
        } else {
            if (! preg_match('~^tcp://([^:]+):(\d+)~', $socket, $m)) {
                throw new ConfigurationError(
                    'Invalid TCP socket syntax: "%s"',
                    $socket
                );
            }
            // TODO: Better config syntax checks
            $this->socket_host = $m[1];
            $this->socket_port = (int) $m[2];
            $this->socket_type = self::TYPE_TCP;
        }
    }

    /**
     * Count unlimited rows matching the query filter
     *
     * TODO: Currently hardcoded value, as the old variant was stupid
     *       Create a working variant doing this->execute(query->renderCount())...
     *
     * @param Query $query the query object
     *
     * @return int
     */
    public function count(Query $query)
    {
    return 100;
        $count = clone($query);
        // WTF? $count->count();
        Benchmark::measure('Sending Livestatus Count Query');
        $this->execute($query);
        $data = $this->fetchRowFromSocket();
        Benchmark::measure('Got Livestatus count result');
        return $data[0][0];
    }

    /**
     * Fetch a single row
     *
     * TODO: Currently based on fetchAll, that's bullshit
     *
     * @param Query $query the query object
     *
     * @return object the first result row
     */
    public function fetchRow(Query $query)
    {
        $all = $this->fetchAll($query);
        return array_shift($all);
    }

    /**
     * Fetch key/value pairs
     *
     * TODO: Currently slow, needs improvement
     *
     * @param Query $query the query object
     *
     * @return array
     */
    public function fetchPairs(Query $query)
    {
        $res = array();
        $all = $this->fetchAll($query);
        foreach ($all as $row) {
            // slow
            $keys = array_keys((array) $row);
            $res[$row->{$keys[0]}] = $row->{$keys[1]};
        }
        return $res;
    }

    /**
     * Fetch all result rows
     *
     * @param Query $query the query object
     *
     * @return array
     */
    public function fetchAll(Query $query)
    {
        Benchmark::measure('Sending Livestatus Query');
        $this->execute($query);
        Benchmark::measure('Got Livestatus Data');

        if ($query->hasColumns()) {
            $headers = $query->getColumnAliases();
        } else {
            // TODO: left this here, find out how to handle it better
            die('F*** no data');
            $headers = array_shift($data);
        }
        $result = array();
        $filter = $query->filterIsSupported() ? null : $query->getFilter();

        while ($row = $this->fetchRowFromSocket()) {
            $r = new ResponseRow($row, $query);
            $res = $query->resultRow($row);
            if ($filter !== null && ! $filter->matches($res)) continue;
            $result[] = $res;
        }

        if ($query->hasOrder()) {
            usort($result, array($query, 'compare'));
        }
        if ($query->hasLimit()) {
            $result = array_slice(
                $result,
                $query->getOffset(),
                $query->getLimit()
            );
        }
        Benchmark::measure('Data sorted, limits applied');

        return $result;
    }

    protected function hasBeenExecuted()
    {
        return $this->status !== null;
    }

    protected function execute($query)
    {
        // Reset state
        $this->status = null;
        $this->responseSize = null;
        $this->bytesRead = 0;

        $raw = $query->toString();

        Benchmark::measure($raw);

        // "debug"
        // echo $raw . "\n<br>";
        $this->writeToSocket($raw);
        $header = $this->readLineFromSocket();

        if (! preg_match('~^(\d{3})\s\s*(\d+)$~', $header, $m)) {
            $this->disconnect();
            throw new Exception(
                sprintf('Got invalid header. First 16 Bytes: %s', $header)
            );
        }
        $this->status = (int) $m[1];
        $this->bytesRead = 0;
        $this->responseSize = (int) $m[2];
        if ($this->status !== 200) {
            // "debug"
            //die(var_export($raw, 1));
            throw new Exception(
                sprintf(
                    'Error %d while querying livestatus: %s %s',
                    $this->status,
                    $raw,
                    $this->readLineFromSocket()
                )
            );
        }
        $this->discoverColumnHeaders($query);
    }

    protected function discoverColumnHeaders($query)
    {
        if ($query->hasColumns()) {
            $this->headers = $query->getColumnAliases();
        } else {
            $this->headers = $this->splitLine($this->readLineFromSocket());
        }
    }

    protected function splitLine(& $line)
    {
        if ($this->headers === null) {
            $res = array();
        } else {
            $res = new SplFixedArray(count($this->headers));
            $size = count($res);
        }
        $start = 0;
        $col = 0;
        while (false !== ($pos = strpos($line, self::FIELD_SEPARATOR, $start))) {
// TODO: safety measure for not killing the SPL. To be removed once code is clean
if ($col > $size -1 ) return $res;  // ???
            $res[$col] = substr($line, $start, $pos - $start);
            $start = $pos + 1;
            $col++;
        }
// TODO: safety measure for not killing the SPL. To be removed once code is clean
if ($col > $size - 1) return $res;
        $res[$col] = rtrim(substr($line, $start), "\r\n");
        return $res;
    }

    public function fetchRowFromSocket()
    {
        $line = $this->readLineFromSocket();
        if (! $line) {
            return false;
        }
        return $this->splitLine($line);
    }

    protected function readLineFromSocket()
    {
        if ($this->bytesRead === $this->responseSize) {
            return false;
        }
        $maxRowLength = 100 * 1024;
        $row = socket_read($this->getConnection(), $maxRowLength, PHP_NORMAL_READ);
        $this->bytesRead += strlen($row);

        if ($row === false) {
            $this->socketError('Failed to read next row from livestatus socket');
        }
        return $row;
    }

    /**
     * Write given string to livestatus socket
     *
     * @param  string $data Data string to write to the socket
     *
     * @return boolean
     */
    protected function writeToSocket($data)
    {
        $res = @socket_write($this->getConnection(), $data);
        if ($res === false) {
            $this->socketError('Writing to livestatus socket failed');
        }
        return true;
    }

    /**
     * Raise an exception showing given message string and last socket error
     *
     * TODO: Find a better exception type for such errors
     *
     * @throws IcingaException
     */
    protected function socketError($msg)
    {
        throw new IcingaException(
            $msg . ': ' . socket_strerror(socket_last_error($this->connection))
        );
    }

    protected function assertPhpExtensionLoaded($name)
    {
        if (! extension_loaded($name)) {
            throw new IcingaException(
                'The extension "%s" is not loaded',
                $name
            );
        }
    }

    protected function getConnection()
    {
        if ($this->connection === null) {
            Benchmark::measure('Establishing livestatus connection...');

            if ($this->socket_type === self::TYPE_TCP) {
                $this->establishTcpConnection();
                Benchmark::measure('...got TCP socket');
            } else {
                $this->establishSocketConnection();
                Benchmark::measure('...got local socket');
            }
        }
        return $this->connection;
    }

    /**
     * Establish a TCP socket connection
     */
    protected function establishTcpConnection()
    {
        $this->connection = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (! @socket_connect($this->connection, $this->socket_host, $this->socket_port)) {
            throw new IcingaException(
                'Cannot connect to livestatus TCP socket "%s:%d": %s',
                $this->socket_host,
                $this->socket_port,
                socket_strerror(socket_last_error($this->connection))
            );
        }
        socket_set_option($this->connection, SOL_TCP, TCP_NODELAY, 1);
    }

    /**
     * Establish a UNIX socket connection
     */
    protected function establishSocketConnection()
    {
        $this->connection = socket_create(AF_UNIX, SOCK_STREAM, 0);
        if (! socket_connect($this->connection, $this->socket_path)) {
            throw new IcingaException(
                'Cannot connect to livestatus local socket "%s"',
                $this->socket_path
            );
        }
    }

    public function connect()
    {
        if (!$this->connection) {
            $this->getConnection();
        }

        return $this;
    }

    /**
     * Disconnect in case we are connected to a Livestatus socket
     *
     * @return $this
     */
    public function disconnect()
    {
        if (is_resource($this->connection)
            && get_resource_type($this->connection) === 'Socket')
        {
            Benchmark::measure('Disconnecting livestatus...');
            socket_close($this->connection);
            Benchmark::measure('...socket closed');
        }
        return $this;
    }

    /**
     * Try to cleanly close the socket on shutdown
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
