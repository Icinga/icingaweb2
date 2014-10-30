<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}}

namespace Icinga\Authentication;

use Zend_Config;
use Icinga\Authentication\Backend\DbUserGroupBackend;
use Icinga\Authentication\Backend\IniUserGroupBackend;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\IcingaException;
use Icinga\User;

/**
 * Base class and factory for user group backends
 */
abstract class UserGroupBackend
{
    /**
     * Name of the backend
     *
     * @var string
     */
    protected $name;

    /**
     * Set the backend name
     *
     * @param   string $name
     *
     * @return  $this
     */
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * Get the backend name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Create a user group backend
     *
     * @param   string      $name
     * @param   Zend_Config $backendConfig
     *
     * @return DbUserGroupBackend|IniUserGroupBackend
     * @throws ConfigurationError If the backend configuration is invalid
     */
    public static function create($name, Zend_Config $backendConfig)
    {
        if ($backendConfig->name !== null) {
            $name = $backendConfig->name;
        }
        if (($backendType = $backendConfig->backend) === null) {
            throw new ConfigurationError(
                'Configuration for user group backend \'%s\' is missing the \'backend\' directive',
                $name
            );
        }
        $backendType = strtolower($backendType);
        if (($resourceName = $backendConfig->resource) === null) {
            throw new ConfigurationError(
                'Configuration for user group backend \'%s\' is missing the \'resource\' directive',
                $name
            );
        }
        $resourceName = strtolower($resourceName);
        try {
            $resource = ResourceFactory::create($resourceName);
        } catch (IcingaException $e) {
            throw new ConfigurationError(
                'Can\'t create user group backend \'%s\'. An exception was thrown: %s',
                $resourceName,
                $e
            );
        }
        switch ($backendType) {
            case 'db':
                $backend = new DbUserGroupBackend($resource);
                break;
            case 'ini':
                $backend = new IniUserGroupBackend($resource);
                break;
            default:
                throw new ConfigurationError(
                    'Can\'t create user group backend \'%s\'. Invalid backend type \'%s\'.',
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
     * @param   User $user
     *
     * @return  array
     */
    abstract public function getMemberships(User $user);
}
