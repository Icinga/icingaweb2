<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\Nrpe;

use Icinga\Exception\IcingaException;

class Connection
{
    protected $host;
    protected $port;
    protected $connection;
    protected $use_ssl = false;
    protected $lastReturnCode = null;

    public function __construct($host, $port = 5666)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function useSsl($use_ssl = true)
    {
        $this->use_ssl = $use_ssl;
        return $this;
    }

    public function sendCommand($command, $args = null)
    {
        if (! empty($args)) {
            $command .= '!' . implode('!', $args);
        }

        $packet = Packet::createQuery($command);
        return $this->send($packet);
    }

    public function getLastReturnCode()
    {
        return $this->lastReturnCode;
    }

    public function send(Packet $packet)
    {
        $conn = $this->connection();
        $bytes = $packet->getBinary();
        fputs($conn, $bytes, strlen($bytes));
        // TODO: Check result checksum!
        $result = fread($conn, 8192);
        if ($result === false) {
            throw new IcingaException('CHECK_NRPE: Error receiving data from daemon.');
        } elseif (strlen($result) === 0) {
            throw new IcingaException(
                'CHECK_NRPE: Received 0 bytes from daemon. Check the remote server logs for error messages'
            );
        }
        // TODO: CHECK_NRPE: Receive underflow - only %d bytes received (%d expected)
        $code = unpack('n', substr($result, 8, 2));
        $this->lastReturnCode = $code[1];
        $this->disconnect();
        return rtrim(substr($result, 10, -2));
    }

    protected function connect()
    {
        $ctx = stream_context_create();
        if ($this->use_ssl) {
            // TODO: fail if not ok:
            $res = stream_context_set_option($ctx, 'ssl', 'ciphers', 'ADH');
            $uri = sprintf('ssl://%s:%d', $this->host, $this->port);
        } else {
            $uri = sprintf('tcp://%s:%d', $this->host, $this->port);
        }
        $this->connection = @stream_socket_client(
            $uri,
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $ctx
        );
        if (! $this->connection) {
            throw new IcingaException(
                'NRPE Connection failed: %s',
                $errstr
            );
        }
    }

    protected function connection()
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    protected function disconnect()
    {
        if (is_resource($this->connection)) {
            fclose($this->connection);
            $this->connection = null;
        }
        return $this;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
