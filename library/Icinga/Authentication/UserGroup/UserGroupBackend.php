<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\UserGroup;

use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\User;

/**
 * Base class and factory for user group backends
 */
abstract class UserGroupBackend
{
    /**
     * The name of this backend
     *
     * @var string
     */
    protected $name;

    /**
     * Set this backend's name
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * Return this backend's name
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Create and return a UserGroupBackend with the given name and given configuration applied to it
     *
     * @param   string          $name
     * @param   ConfigObject    $backendConfig
     *
     * @return  UserGroupBackend
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
            case 'ini':
                $backend = new IniUserGroupBackend($resource);
                break;
            default:
                throw new ConfigurationError(
                    'Configuration for user group backend "%s" defines an invalid backend type.'
                    . ' Backend type "%s" is not supported',
                    $name,
                    $backendType
                );
        }

        $backend->setName($name);
        return $backend;
    }

    /**
     * Get the groups the given user is a member of
     *
     * @param   User    $user
     *
     * @return  array
     */
    abstract public function getMemberships(User $user);
}
