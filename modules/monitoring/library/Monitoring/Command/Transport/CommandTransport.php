<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Monitoring\Command\Transport;

use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;

/**
 * Command transport factory
 *
 * This class is subject to change as we do not have environments yet (#4471).
 */
abstract class CommandTransport
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
     * @return Config
     * @throws ConfigurationError
     */
    public static function getConfig()
    {
        if (! isset(self::$config)) {
            self::$config = Config::module('monitoring', 'instances');
            if (self::$config->isEmpty()) {
                throw new ConfigurationError(
                    'No instances have been configured in \'%s\'.',
                    self::$config->getConfigFile()
                );
            }
        }
        return self::$config;
    }

    /**
     * Create a transport from config
     *
     * @param   ConfigObject  $config
     *
     * @return  LocalCommandFile|RemoteCommandFile
     * @throws  ConfigurationError
     */
    public static function fromConfig(ConfigObject $config)
    {
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
                    'Can\'t create command transport \'%s\'. Invalid transport defined in \'%s\'.'
                    . ' Use one of \'%s\' or \'%s\'.',
                    $config->transport,
                    self::$config->getConfigFile(),
                    LocalCommandFile::TRANSPORT,
                    RemoteCommandFile::TRANSPORT
                );
        }
        unset($config->transport);
        foreach ($config as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (! method_exists($transport, $method)) {
                throw new ConfigurationError();
            }
            $transport->$method($value);
        }
        return $transport;
    }

    /**
     * Create a transport by name
     *
     * @param   string $name
     *
     * @return  LocalCommandFile|RemoteCommandFile
     * @throws  ConfigurationError
     */
    public static function create($name)
    {
        $config = self::getConfig()->getSection($name);
        if ($config->isEmpty()) {
            throw new ConfigurationError();
        }
        return self::fromConfig($config);
    }

    /**
     * Create a transport by the first section of the configuration
     *
     * @return LocalCommandFile|RemoteCommandFile
     */
    public static function first()
    {
        $config = self::getConfig()->current();
        return self::fromConfig($config);
    }
}
