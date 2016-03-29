<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\UserGroup;

use Icinga\Application\Logger;
use Icinga\Application\Icinga;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;

/**
 * Factory for user group backends
 */
class UserGroupBackend
{
    /**
     * The default user group backend types provided by Icinga Web 2
     *
     * @var array
     */
    protected static $defaultBackends = array(
        'db',
        'ldap',
        'msldap'
    );

    /**
     * The registered custom user group backends with their identifier as key and class name as value
     *
     * @var array
     */
    protected static $customBackends;

    /**
     * Register all custom user group backends from all loaded modules
     */
    public static function registerCustomUserGroupBackends()
    {
        if (static::$customBackends !== null) {
            return;
        }

        static::$customBackends = array();
        $providedBy = array();
        foreach (Icinga::app()->getModuleManager()->getLoadedModules() as $module) {
            foreach ($module->getUserGroupBackends() as $identifier => $className) {
                if (array_key_exists($identifier, $providedBy)) {
                    Logger::warning(
                        'Cannot register user group backend of type "%s" provided by module "%s".'
                        . ' The type is already provided by module "%s"',
                        $identifier,
                        $module->getName(),
                        $providedBy[$identifier]
                    );
                } elseif (in_array($identifier, static::$defaultBackends)) {
                    Logger::warning(
                        'Cannot register user group backend of type "%s" provided by module "%s".'
                        . ' The type is a default type provided by Icinga Web 2',
                        $identifier,
                        $module->getName()
                    );
                } else {
                    $providedBy[$identifier] = $module->getName();
                    static::$customBackends[$identifier] = $className;
                }
            }
        }
    }

    /**
     * Return the class for the given custom user group backend
     *
     * @param   string  $identifier     The identifier of the custom user group backend
     *
     * @return  string|null             The name of the class or null in case there was no
     *                                   backend found with the given identifier
     *
     * @throws  ConfigurationError      In case the class associated to the given identifier does not exist
     */
    protected static function getCustomUserGroupBackend($identifier)
    {
        static::registerCustomUserGroupBackends();
        if (array_key_exists($identifier, static::$customBackends)) {
            $className = static::$customBackends[$identifier];
            if (! class_exists($className)) {
                throw new ConfigurationError(
                    'Cannot utilize user group backend of type "%s". Class "%s" does not exist',
                    $identifier,
                    $className
                );
            }

            return $className;
        }
    }

    /**
     * Create and return a user group backend with the given name and given configuration applied to it
     *
     * @param   string          $name
     * @param   ConfigObject    $backendConfig
     *
     * @return  UserGroupBackendInterface
     *
     * @throws  ConfigurationError
     */
    public static function create($name, ConfigObject $backendConfig)
    {
        if ($backendConfig->name !== null) {
            $name = $backendConfig->name;
        }

        if (! ($backendType = strtolower($backendConfig->backend))) {
            throw new ConfigurationError(
                'Configuration for user group backend "%s" is missing the \'backend\' directive',
                $name
            );
        }
        if (in_array($backendType, static::$defaultBackends)) {
            // The default backend check is the first one because of performance reasons:
            // Do not attempt to load a custom user group backend unless it's actually required
        } elseif (($customClass = static::getCustomUserGroupBackend($backendType)) !== null) {
            $backend = new $customClass($backendConfig);
            if (! is_a($backend, 'Icinga\Authentication\UserGroup\UserGroupBackendInterface')) {
                throw new ConfigurationError(
                    'Cannot utilize user group backend of type "%s".'
                    . ' Class "%s" does not implement UserGroupBackendInterface',
                    $backendType,
                    $customClass
                );
            }

            $backend->setName($name);
            return $backend;
        } else {
            throw new ConfigurationError(
                'Configuration for user group backend "%s" defines an invalid backend type.'
                . ' Backend type "%s" is not supported',
                $name,
                $backendType
            );
        }

        if ($backendConfig->resource === null) {
            throw new ConfigurationError(
                'Configuration for user group backend "%s" is missing the \'resource\' directive',
                $name
            );
        }
        $resource = ResourceFactory::create($backendConfig->resource);

        switch ($backendType) {
            case 'db':
                $backend = new DbUserGroupBackend($resource);
                break;
            case 'ldap':
            case 'msldap':
                $backend = new LdapUserGroupBackend($resource);
                $backend->setConfig($backendConfig);
                break;
        }

        $backend->setName($name);
        return $backend;
    }
}
