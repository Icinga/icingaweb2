<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

use Icinga\Application\Config;
use Icinga\Util\ConfigAwareFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Data\Db\DbConnection;
use Icinga\Protocol\Ldap\LdapConnection;
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
     * Get the configuration of all existing resources, or all resources of the given type
     *
     * @param   string  $type   Filter for resource type
     *
     * @return  Config          The resources configuration
     */
    public static function getResourceConfigs($type = null)
    {
        self::assertResourcesExist();
        if ($type === null) {
            return self::$resources;
        }
        $resources = array();
        foreach (self::$resources as $name => $resource) {
            if ($resource->get('type') === $type) {
                $resources[$name] = $resource;
            }
        }
        return Config::fromArray($resources);
    }

    /**
     * Check if the existing resources are set. If not, load them from resources.ini
     *
     * @throws  ConfigurationError
     */
    private static function assertResourcesExist()
    {
        if (self::$resources === null) {
            self::$resources = Config::app('resources');
        }
    }

    /**
     * Create and return a resource based on the given configuration
     *
     * @param   ConfigObject    $config     The configuration of the resource to create
     *
     * @return  Selectable                  The resource
     * @throws  ConfigurationError          In case of an unsupported type or invalid configuration
     */
    public static function createResource(ConfigObject $config)
    {
        switch (strtolower($config->type)) {
            case 'db':
                $resource = new DbConnection($config);
                break;
            case 'ldap':
                if (empty($config->root_dn)) {
                    throw new ConfigurationError('LDAP root DN missing');
                }

                $resource = new LdapConnection($config);
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
     * @return  DbConnection|LdapConnection
     */
    public static function create($resourceName)
    {
        return self::createResource(self::getResourceConfig($resourceName));
    }
}
