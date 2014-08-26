<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Commandpipe\Transport;

use RuntimeException;
use Zend_Config;
use Icinga\Logger\Logger;

/**
 * Command pipe transport class that uses ssh for connecting to a remote filesystem with the icinga.cmd pipe
 *
 * The remote host must have KeyAuth enabled for this user
 */
class SecureShell implements Transport
{
    /**
     * The remote host to connect to
     *
     * @var string
     */
    private $host = 'localhost';

    /**
     * The location of the icinga pipe on the remote host
     *
     * @var string
     */
    private $path = "/usr/local/icinga/var/rw/icinga.cmd";

    /**
     * The SSH port of the remote host
     *
     * @var int
     */
    private $port = 22;

    /**
     * The user to authenticate with on the remote host
     *
     * @var String
     */
    private $user = null;

    /**
     * Overwrite the target file of this Transport class using the given config from instances.ini
     *
     * @param   Zend_Config $config
     *
     * @see     Transport::setEndpoint()
     */
    public function setEndpoint(Zend_Config $config)
    {
        $this->host = isset($config->host) ? $config->host : 'localhost';
        $this->port = isset($config->port) ? $config->port : 22;
        $this->user = isset($config->user) ? $config->user : null;
        $this->path = isset($config->path) ? $config->path : '/usr/local/icinga/var/rw/icinga.cmd';
    }

    /**
     * Write the given external command to the command pipe
     *
     * @param   string $command
     *
     * @throws  RuntimeException When the command could not be sent to the remote Icinga host
     * @see     Transport::send()
     */
    public function send($command)
    {
        $retCode = 0;
        $output = array();
        Logger::debug(
            'Icinga instance is on different host, attempting to send command %s via ssh to %s:%s/%s',
            $command,
            $this->host,
            $this->port,
            $this->path
        );
        $hostConnector = $this->user ? $this->user . "@" . $this->host : $this->host;
        $command = escapeshellarg('['. time() .'] ' . $command);
        $sshCommand = sprintf(
            'ssh -o BatchMode=yes -o KbdInteractiveAuthentication=no %s -p %d'
          . ' "echo %s > %s" 2>&1',
            $hostConnector,
            $this->port,
            $command,
            $this->path
        );

        exec($sshCommand, $output, $retCode);
        Logger::debug("Command '%s' exited with %d: %s", $sshCommand, $retCode, $output);

        if ($retCode != 0) {
            $msg =  'Could not send command to remote Icinga host: '
                . implode(PHP_EOL, $output)
                . " (returncode $retCode)";
            Logger::error($msg);
            throw new RuntimeException($msg);
        }
    }
}
