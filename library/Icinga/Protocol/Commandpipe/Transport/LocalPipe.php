<?php

namespace Icinga\Protocol\Commandpipe\Transport;
use Icinga\Application\Logger;

/**
 * CommandPipe Transport class that writes to a file accessible by the filesystem
 *
 */
class LocalPipe implements Transport
{
    /**
     * The path of the icinga commandpipe
     *
     * @var String
     */
    private $path;

    /**
     * The mode to use for fopen()
     *
     * @var string
     */
    private $openMode = "w";

    /**
     * @see Transport::setEndpoint()
     */
    public function setEndpoint(\Zend_Config $config)
    {
        $this->path = isset($config->path) ? $config->path : '/usr/local/icinga/var/rw/icinga.cmd';
    }

    /**
     *  @see Transport::send()
     */
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

    /**
     * Overwrite the open mode (useful for testing)
     *
     * @param string $mode          A open mode supported by fopen()
     */
    public function setOpenMode($mode)
    {
        $this->openMode = $mode;
    }
}