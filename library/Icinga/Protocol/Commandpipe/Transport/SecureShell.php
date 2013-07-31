<?php

namespace Icinga\Protocol\Commandpipe\Transport;

use Icinga\Application\Logger;

class SecureShell implements Transport
{
    private $host = 'localhost';
    private $path = "/usr/local/icinga/var/rw/icinga.cmd";
    private $port = 22;
    private $user = null;
    private $password = null;

    public function setEndpoint(\Zend_Config $config)
    {
        $this->host = isset($config->host) ? $config->host : "localhost";
        $this->port = isset($config->port) ? $config->port : 22;
        $this->user = isset($config->user) ? $config->user : null;
        $this->password = isset($config->password) ? $config->password : null;
        $this->path = isset($config->path) ? $config->path : "/usr/local/icinga/var/rw/icinga.cmd";
    }

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
        exec(
            'ssh -o BatchMode=yes -o KbdInteractiveAuthentication=no'.$hostConnector.' -p'.$this->port.' "echo \'['. time() .'] '
            . escapeshellcmd(
                $command
            )
            . '\' > '.$this->path.'" > /dev/null 2> /dev/null & ',
            $output,
            $retCode
        );
        Logger::debug(
            'ssh '.$hostConnector.' -p'.$this->port.' "echo \'['. time() .'] '
            . escapeshellcmd(
                $command
            )
            . '\' > '.$this->path.'"'
        );
        Logger::debug("Return code %s: %s ", $retCode, $output);

        if ($retCode != 0) {
            $msg =  'Could not send command to remote icinga host: '. implode("\n",$output). " (returncode $retCode)";
            Logger::error($msg);
            throw new \RuntimeException($msg);
        }
    }
}