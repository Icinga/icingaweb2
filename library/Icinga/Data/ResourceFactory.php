<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

use Icinga\Application\Config;
use Icinga\Util\ConfigAwareFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Data\Db\DbConnection;
use Icinga\Protocol\Livestatus\Connection as LivestatusConnection;
use Icinga\Protocol\Ldap\Connection as LdapConnection;
use Icinga\Protocol\File\FileReader;

/**
 * Create resources from names or resource configuration
 */
class ResourceFactory implements ConfigAwareFactory
{
    /**
     * Resource configuration
     *
     * @var Config
     */
    private static $resources;

    /**
     * Set resource configurations
     *
     * @param Config $config
     */
    public static function setConfig($config)
    {
        self::$resources = $config;
    }

    /**
     * Get the configuration for a specific resource
     *
     * @param   $resourceName   String      The resource's name
     *
     * @return                  ConfigObject    The configuration of the resource
     *
     * @throws                  ConfigurationError
     */
    public static function getResourceConfig($resourceName)
    {
        self::assertResourcesExist();
        $resourceConfig = self::$resources->getSection($resourceName);
        if ($resourceConfig->isEmpty()) {
            throw new ConfigurationError(
                'Cannot load resource config "%s". Resource does not exist',
                $resourceName
            );
        }
        return $resourceConfig;
    }

    /**
     * Return the configuration of all existing resources, or get all resources of a given type.
     *
     * @return Config          The configuration containing all resources
     */
    public static function getResourceConfigs()
    {
        self::assertResourcesExist();
        return self::$resources;
    }

    /**
     * Check if the existing resources are set. If not, throw an error.
     *
     * @throws  ConfigurationError
     */
    private static function assertResourcesExist()
    {
        if (self::$resources === null) {
            throw new ConfigurationError(
                'Resources not set up. Please contact your Icinga Web administrator'
            );
        }
    }

    /**
     * Create a single resource from the given configuration.
     *
     * NOTE: The factory does not test if the given configuration is valid and the resource is accessible, this
     * depends entirely on the implementation of the returned resource.
     *
     * @param ConfigObject $config                   The configuration for the created resource.
     *
     * @return DbConnection|LdapConnection|LivestatusConnection An object that can be used to access
     *         the given resource. The returned class depends on the configuration property 'type'.
     * @throws ConfigurationError When an unsupported type is given
     */
    public static function createResource(ConfigObject $config)
    {
        switch (strtolower($config->type)) {
            case 'db':
                $resource = new DbConnection($config);
                break;
            case 'ldap':
                $resource = new LdapConnection($config);
                break;
            case 'livestatus':
                $resource = new LivestatusConnection($config->socket);
                break;
            case 'file':
                $resource = new FileReader($config);
                break;
            case 'ini':
                $resource = Config::fromIni($config->ini);
                break;
            default:
                throw new ConfigurationError(
                    'Unsupported resource type "%s"',
                    $config->type
                );
        }
        return $resource;
    }

    /**
     * Create a resource from name
     *
     * @param   string  $resourceName
     * @return  DbConnection|LdapConnection|LivestatusConnection
     */
    public static function create($resourceName)
    {
        return self::createResource(self::getResourceConfig($resourceName));
    }
}
