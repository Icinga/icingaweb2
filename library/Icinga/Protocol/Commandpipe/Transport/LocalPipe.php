<?php

namespace Icinga\Protocol\Commandpipe\Transport;
use Icinga\Application\Logger;

class LocalPipe implements Transport
{
    private $path;
    private $openMode = "w";

    public function setEndpoint(\Zend_Config $config)
    {
        $this->path = isset($config->path) ? $config->path : '/usr/local/icinga/var/rw/icinga.cmd';
    }

    public function send($message)
    {
        Logger::debug('Attempting to send external icinga command %s to local command file ', $message, $this->path);
        $file = @fopen($this->path, $this->openMode);
        if (!$file) {
            throw new \RuntimeException('Could not open icinga pipe at $file : ' . print_r(error_get_last(), true));
        }
        fwrite($file, '[' . time() . '] ' . $message . PHP_EOL);
        Logger::debug('Writing [' . time() . '] ' . $message . PHP_EOL);
        fclose($file);
    }

    public function setOpenMode($mode)
    {
        $this->openMode = $mode;
    }
}