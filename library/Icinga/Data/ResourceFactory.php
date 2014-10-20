<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Data;

use Zend_Config;
use Icinga\Application\Config;
use Icinga\Exception\ProgrammingError;
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
     * @var Zend_Config
     */
    private static $resources;

    /**
     * Set resource configurations
     *
     * @param Zend_Config $config
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
     * @return                  Zend_Config The configuration of the resource
     *
     * @throws                  ConfigurationError
     */
    public static function getResourceConfig($resourceName)
    {
        self::assertResourcesExist();
        if (($resourceConfig = self::$resources->get($resourceName)) === null) {
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
     * @param  String|null  $type   Fetch only resources that have the given type.
     *
     * @return Zend_Config          The configuration containing all resources
     */
    public static function getResourceConfigs($type = null)
    {
        self::assertResourcesExist();
        if (!isset($type)) {
            return self::$resources;
        } else {
            $resources = array();
            foreach (self::$resources as $name => $resource) {
                if (strtolower($resource->type) === $type) {
                    $resources[$name] = $resource;
                }
            }
            return new Zend_Config($resources);
        }
    }

    /**
     * Check if the existing resources are set. If not, throw an error.
     *
     * @throws ProgrammingError
     */
    private static function assertResourcesExist()
    {
        if (!isset(self::$resources)) {
            throw new ProgrammingError(
                'The ResourceFactory must be initialised by setting a config, before it can be used'
            );
        }
    }

    /**
     * Create a single resource from the given configuration.
     *
     * NOTE: The factory does not test if the given configuration is valid and the resource is accessible, this
     * depends entirely on the implementation of the returned resource.
     *
     * @param Zend_Config $config                   The configuration for the created resource.
     *
     * @return DbConnection|LdapConnection|LivestatusConnection An object that can be used to access
     *         the given resource. The returned class depends on the configuration property 'type'.
     * @throws ConfigurationError When an unsupported type is given
     */
    public static function createResource(Zend_Config $config)
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
