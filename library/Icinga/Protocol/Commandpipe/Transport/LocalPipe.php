<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Commandpipe\Transport;

use Exception;
use Icinga\Util\File;
use Icinga\Logger\Logger;
use Icinga\Exception\ConfigurationError;

/**
 * CommandPipe Transport class that writes to a file accessible by the filesystem
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
     * The mode to use to access the pipe
     *
     * @var string
     */
    private $openMode = "wn";

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

        try {
            $file = new File($this->path, $this->openMode);
            $file->fwrite('[' . time() . '] ' . $message . PHP_EOL);
            $file->fflush();
        } catch (Exception $e) {
            throw new ConfigurationError(
                sprintf(
                    'Could not open icinga command pipe at "%s" (%s)',
                    $this->path,
                    $e->getMessage()
                )
            );
        }

        Logger::debug('Command sent: [' . time() . '] ' . $message . PHP_EOL);
    }

    /**
     * Overwrite the open mode (useful for testing)
     *
     * @param   string  $mode   The mode to use to access the pipe
     */
    public function setOpenMode($mode)
    {
        $this->openMode = $mode;
    }
}
