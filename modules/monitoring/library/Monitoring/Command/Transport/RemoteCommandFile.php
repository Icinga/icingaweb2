<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Transport;

use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Monitoring\Command\Exception\TransportException;
use Icinga\Module\Monitoring\Command\IcingaCommand;
use Icinga\Module\Monitoring\Command\Renderer\IcingaCommandFileCommandRenderer;

/**
 * A remote Icinga command file
 *
 * Key-based SSH login must be possible for the user to log in as on the remote host
 */
class RemoteCommandFile implements CommandTransportInterface
{
    /**
     * Transport identifier
     */
    const TRANSPORT = 'remote';

    /**
     * Remote host
     *
     * @var string
     */
    protected $host;

    /**
     * Port to connect to on the remote host
     *
     * @var int
     */
    protected $port = 22;

    /**
     * User to log in as on the remote host
     *
     * Defaults to current PHP process' user
     *
     * @var string
     */
    protected $user;

    /**
     * Path to the Icinga command file on the remote host
     *
     * @var string
     */
    protected $path;

    /**
     * Command renderer
     *
     * @var IcingaCommandFileCommandRenderer
     */
    protected $renderer;

    /**
     * Create a new remote command file command transport
     */
    public function __construct()
    {
        $this->renderer = new IcingaCommandFileCommandRenderer();
    }

    /**
     * Set the remote host
     *
     * @param   string $host
     *
     * @return  $this
     */
    public function setHost($host)
    {
        $this->host = (string) $host;
        return $this;
    }

    /**
     * Get the remote host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the port to connect to on the remote host
     *
     * @param   int $port
     *
     * @return  $this
     */
    public function setPort($port)
    {
        $this->port = (int) $port;
        return $this;
    }

    /**
     * Get the port to connect on the remote host
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the user to log in as on the remote host
     *
     * @param   string $user
     *
     * @return  $this
     */
    public function setUser($user)
    {
        $this->user = (string) $user;
        return $this;
    }

    /**
     * Get the user to log in as on the remote host
     *
     * Defaults to current PHP process' user
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the path to the Icinga command file on the remote host
     *
     * @param   string $path
     *
     * @return  $this
     */
    public function setPath($path)
    {
        $this->path = (string) $path;
        return $this;
    }

    /**
     * Get the path to the Icinga command file on the remote host
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Write the command to the Icinga command file on the remote host
     *
     * @param   IcingaCommand   $command
     * @param   int|null        $now
     *
     * @throws  ConfigurationError
     * @throws  TransportException
     */
    public function send(IcingaCommand $command, $now = null)
    {
        if (! isset($this->path)) {
            throw new ConfigurationError(
                'Can\'t send external Icinga Command. Path to the remote command file is missing'
            );
        }
        if (! isset($this->host)) {
            throw new ConfigurationError('Can\'t send external Icinga Command. Remote host is missing');
        }
        $commandString = $this->renderer->render($command, $now);
        Logger::debug(
            'Sending external Icinga command "%s" to the remote command file "%s:%u%s"',
            $commandString,
            $this->host,
            $this->port,
            $this->path
        );
        $ssh = sprintf('ssh -o BatchMode=yes -p %u', $this->port);
        // -o BatchMode=yes for disabling interactive authentication methods
        if (isset($this->user)) {
            $ssh .= sprintf(' -l %s', escapeshellarg($this->user));
        }
        $ssh .= sprintf(
            ' %s "echo %s > %s" 2>&1', // Redirect stderr to stdout
            escapeshellarg($this->host),
            escapeshellarg($commandString),
            escapeshellarg($this->path)
        );
        exec($ssh, $output, $status);
        if ($status !== 0) {
            throw new TransportException(
                'Can\'t send external Icinga command "%s": %s',
                $ssh,
                implode(' ', $output)
            );
        }
    }
}
