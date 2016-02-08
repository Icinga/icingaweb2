<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Transport;

use Exception;
use RuntimeException;
use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Monitoring\Command\IcingaCommand;
use Icinga\Module\Monitoring\Command\Renderer\IcingaCommandFileCommandRenderer;
use Icinga\Module\Monitoring\Exception\CommandTransportException;
use Icinga\Util\File;

/**
 * A local Icinga command file
 */
class LocalCommandFile implements CommandTransportInterface
{
    /**
     * Transport identifier
     */
    const TRANSPORT = 'local';

    /**
     * The name of the Icinga instance this transport will transfer commands to
     *
     * @var string
     */
    protected $instanceName;

    /**
     * Path to the icinga command file
     *
     * @var String
     */
    protected $path;

    /**
     * Mode used to open the icinga command file
     *
     * @var string
     */
    protected $openMode = 'wn';

    /**
     * Command renderer
     *
     * @var IcingaCommandFileCommandRenderer
     */
    protected $renderer;

    /**
     * Create a new local command file command transport
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
     * Set the path to the local Icinga command file
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
     * Get the path to the local Icinga command file
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the mode used to open the icinga command file
     *
     * @param   string $openMode
     *
     * @return  $this
     */
    public function setOpenMode($openMode)
    {
        $this->openMode = (string) $openMode;
        return $this;
    }

    /**
     * Get the mode used to open the icinga command file
     *
     * @return string
     */
    public function getOpenMode()
    {
        return $this->openMode;
    }

    /**
     * Write the command to the local Icinga command file
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
                'Can\'t send external Icinga Command. Path to the local command file is missing'
            );
        }
        $commandString = $this->renderer->render($command, $now);
        Logger::debug(
            'Sending external Icinga command "%s" to the local command file "%s"',
            $commandString,
            $this->path
        );
        try {
            $file = new File($this->path, $this->openMode);
            $file->fwrite($commandString . "\n");
        } catch (Exception $e) {
            $message = $e->getMessage();
            if ($e instanceof RuntimeException && ($pos = strrpos($message, ':')) !== false) {
                // Assume RuntimeException thrown by SplFileObject in the format: __METHOD__ . "({$filename}): Message"
                $message = substr($message, $pos + 1);
            }
            throw new CommandTransportException(
                'Can\'t send external Icinga command to the local command file "%s": %s',
                $this->path,
                $message
            );
        }
    }
}
