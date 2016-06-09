<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Transport;

use Icinga\Application\Logger;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Monitoring\Command\IcingaCommand;
use Icinga\Module\Monitoring\Command\Renderer\IcingaCommandFileCommandRenderer;
use Icinga\Module\Monitoring\Exception\CommandTransportException;

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
     * The name of the Icinga instance this transport will transfer commands to
     *
     * @var string
     */
    protected $instanceName;

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
     * Path to the private key file for the key-based authentication
     *
     * @var string
     */
    protected $privateKey;

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
     * SSH subprocess pipes
     *
     * @var array
     */
    protected $sshPipes;

    /**
     * SSH subprocess
     *
     * @var resource
     */
    protected $sshProcess;

    /**
     * Create a new remote command file command transport
     */
    public function __construct()
    {
        $this->renderer = new IcingaCommandFileCommandRenderer();
    }

    /**
     * Set the name of the Icinga instance this transport will transfer commands to
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function setInstance($name)
    {
        $this->instanceName = $name;
        return $this;
    }

    /**
     * Return the name of the Icinga instance this transport will transfer commands to
     *
     * @return  string
     */
    public function getInstance()
    {
        return $this->instanceName;
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
     * Set the path to the private key file
     *
     * @param string $privateKey
     *
     * @return $this
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = (string) $privateKey;
        return $this;
    }

    /**
     * Get the path to the private key
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Use a given resource to set the user and the key
     *
     * @param string
     *
     * @throws ConfigurationError
     */
    public function setResource($resource = null)
    {
        $config = ResourceFactory::getResourceConfig($resource);

        if (! isset($config->user)) {
            throw new ConfigurationError(
                t("Can't send external Icinga Command. Remote user is missing")
            );
        }
        if (! isset($config->private_key)) {
            throw new ConfigurationError(
                t("Can't send external Icinga Command. The private key for the remote user is missing")
            );
        }

        $this->setUser($config->user);
        $this->setPrivateKey($config->private_key);
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
     * @throws  CommandTransportException
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
        return $this->sendCommandString($commandString);
    }

    /**
     * Get the SSH command
     *
     * @return  string
     */
    protected function sshCommand()
    {
        $cmd = sprintf(
            'exec ssh -o BatchMode=yes -p %u',
            $this->port
        );
        // -o BatchMode=yes for disabling interactive authentication methods

        if (isset($this->user)) {
            $cmd .= ' -l ' . escapeshellarg($this->user);
        }

        if (isset($this->privateKey)) {
            // TODO: StrictHostKeyChecking=no for compat only, must be removed
            $cmd .= ' -o StrictHostKeyChecking=no'
                  . ' -i ' . escapeshellarg($this->privateKey);
        }

        $cmd .= sprintf(
            ' %s "cat > %s"',
            escapeshellarg($this->host),
            escapeshellarg($this->path)
        );

        return $cmd;
    }

    /**
     * Send the command over SSH
     *
     * @param   string  $commandString
     *
     * @throws  CommandTransportException
     */
    protected function sendCommandString($commandString)
    {
        if ($this->isSshAlive()) {
            $ret = fwrite($this->sshPipes[0], $commandString . "\n");
            if ($ret === false) {
                $this->throwSshFailure('Cannot write to the remote command pipe');
            } elseif ($ret !== strlen($commandString) + 1) {
                $this->throwSshFailure(
                    'Failed to write the whole command to the remote command pipe'
                );
            }
        } else {
            $this->throwSshFailure();
        }
    }

    /**
     * Get the pipes of the SSH subprocess
     *
     * @return  array
     */
    protected function getSshPipes()
    {
        if ($this->sshPipes === null) {
            $this->forkSsh();
        }

        return $this->sshPipes;
    }

    /**
     * Get the SSH subprocess
     *
     * @return  resource
     */
    protected function getSshProcess()
    {
        if ($this->sshProcess === null) {
            $this->forkSsh();
        }

        return $this->sshProcess;
    }

    /**
     * Get the status of the SSH subprocess
     *
     * @param   string  $what
     *
     * @return  mixed
     */
    protected function getSshProcessStatus($what = null)
    {
        $status = proc_get_status($this->getSshProcess());
        if ($what === null) {
            return $status;
        } else {
            return $status[$what];
        }
    }

    /**
     * Get whether the SSH subprocess is alive
     *
     * @return  bool
     */
    protected function isSshAlive()
    {
        return $this->getSshProcessStatus('running');
    }

    /**
     * Fork SSH subprocess
     *
     * @throws  CommandTransportException   If fork fails
     */
    protected function forkSsh()
    {
        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );

        $this->sshProcess = proc_open($this->sshCommand(), $descriptors, $this->sshPipes);

        if (! is_resource($this->sshProcess)) {
            throw new CommandTransportException(
                'Can\'t send external Icinga command: Failed to fork SSH'
            );
        }
    }

    /**
     * Read from STDERR
     *
     * @return  string
     */
    protected function readStderr()
    {
        return stream_get_contents($this->sshPipes[2]);
    }

    /**
     * Throw SSH failure
     *
     * @param   string  $msg
     *
     * @throws  CommandTransportException
     */
    protected function throwSshFailure($msg = 'Can\'t send external Icinga command')
    {
        throw new CommandTransportException(
            '%s: %s',
            $msg,
            $this->readStderr() . var_export($this->getSshProcessStatus(), true)
        );
    }

    /**
     * Close SSH pipes and SSH subprocess
     */
    public function __destruct()
    {
        if (is_resource($this->sshProcess)) {
            fclose($this->sshPipes[0]);
            fclose($this->sshPipes[1]);
            fclose($this->sshPipes[2]);

            proc_close($this->sshProcess);
        }
    }
}
