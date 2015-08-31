<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Transport;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Monitoring\Command\IcingaCommand;
use Icinga\Module\Monitoring\Command\Object\ObjectCommand;
use Icinga\Module\Monitoring\Exception\CommandTransportException;

/**
 * Command transport
 *
 * This class is subject to change as we do not have environments yet (#4471).
 */
class CommandTransport implements CommandTransportInterface
{
    /**
     * Transport configuration
     *
     * @var Config
     */
    protected static $config;

    /**
     * Get transport configuration
     *
     * @return  Config
     *
     * @throws  ConfigurationError
     */
    public static function getConfig()
    {
        if (static::$config === null) {
            $config = Config::module('monitoring', 'commandtransports');
            if ($config->isEmpty()) {
                throw new ConfigurationError(
                    mt('monitoring', 'No command transports have been configured in "%s".'),
                    $config->getConfigFile()
                );
            }

            static::$config = $config;
        }

        return static::$config;
    }

    /**
     * Create a transport from config
     *
     * @param   ConfigObject  $config
     *
     * @return  LocalCommandFile|RemoteCommandFile
     *
     * @throws  ConfigurationError
     */
    public static function createTransport(ConfigObject $config)
    {
        $config = clone $config;
        switch (strtolower($config->transport)) {
            case RemoteCommandFile::TRANSPORT:
                $transport = new RemoteCommandFile();
                break;
            case LocalCommandFile::TRANSPORT:
            case '':  // Casting null to string is the empty string
                $transport = new LocalCommandFile();
                break;
            default:
                throw new ConfigurationError(
                    mt(
                        'monitoring',
                        'Cannot create command transport "%s". Invalid transport'
                        . ' defined in "%s". Use one of "%s" or "%s".'
                    ),
                    $config->transport,
                    static::getConfig()->getConfigFile(),
                    LocalCommandFile::TRANSPORT,
                    RemoteCommandFile::TRANSPORT
                );
        }

        unset($config->transport);
        foreach ($config as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (! method_exists($transport, $method)) {
                // Ignore settings from config that don't have a setter on the transport instead of throwing an
                // exception here because the transport should throw an exception if it's not fully set up
                // when being about to send a command
                continue;
            }

            $transport->$method($value);
        }

        return $transport;
    }

    /**
     * Send the given command over an appropriate Icinga command transport
     *
     * This will try one configured transport after another until the command has been successfully sent.
     *
     * @param   IcingaCommand   $command    The command to send
     * @param   int|null        $now        Timestamp of the command or null for now
     *
     * @throws  CommandTransportException   If sending the Icinga command failed
     */
    public function send(IcingaCommand $command, $now = null)
    {
        $tries = 0;
        foreach (static::getConfig() as $transportConfig) {
            $transport = static::createTransport($transportConfig);

            if ($this->transferPossible($command, $transport)) {
                try {
                    $transport->send($command, $now);
                } catch (CommandTransportException $e) {
                    Logger::error($e);
                    $tries += 1;
                    continue; // Try the next transport
                }

                return; // The command was successfully sent
            }
        }

        if ($tries > 0) {
            throw new CommandTransportException(
                mt(
                    'monitoring',
                    'Failed to send external Icinga command. None of the configured transports'
                    . ' was able to transfer the command. Please see the log for more details.'
                )
            );
        }

        throw new CommandTransportException(
            mt(
                'monitoring',
                'Failed to send external Icinga command. No transport has been configured'
                . ' for this instance. Please contact your Icinga Web administrator.'
            )
        );
    }

    /**
     * Return whether it is possible to send the given command using the given transport
     *
     * @param   IcingaCommand               $command
     * @param   CommandTransportInterface   $transport
     *
     * @return  bool
     */
    protected function transferPossible($command, $transport)
    {
        if (! method_exists($transport, 'getInstance') || !$command instanceof ObjectCommand) {
            return true;
        }

        $transportInstance = $transport->getInstance();
        if (! $transportInstance || $transportInstance === 'none') {
            return true;
        }

        return strtolower($transportInstance) === strtolower($command->getObject()->instance_name);
    }
}
