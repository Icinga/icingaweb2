<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}}

namespace Icinga\Authentication;

use Countable;
use Icinga\Authentication\Backend\AutoLoginBackend;
use Zend_Config;
use Icinga\Authentication\Backend\DbUserBackend;
use Icinga\Authentication\Backend\LdapUserBackend;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\User;

abstract class UserBackend implements Countable
{
    /**
     * Name of the backend
     *
     * @var string
     */
    protected $name;

    /**
     * Setter for the backend's name
     *
     * @param   string $name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Getter for the backend's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public static function create($name, Zend_Config $backendConfig)
    {
        if ($backendConfig->name !== null) {
            $name = $backendConfig->name;
        }
        if (isset($backendConfig->class)) {
            // Use a custom backend class, this is only useful for testing
            if (!class_exists($backendConfig->class)) {
                throw new ConfigurationError(
                    'Authentication configuration for backend "%s" defines an invalid backend class.'
                    . ' Backend class "%s" not found',
                    $name,
                    $backendConfig->class
                );
            }
            return new $backendConfig->class($backendConfig);
        }
        if (($backendType = $backendConfig->backend) === null) {
            throw new ConfigurationError(
                'Authentication configuration for backend "%s" is missing the backend directive',
                $name
            );
        }
        $backendType = strtolower($backendType);
        if ($backendType === 'autologin') {
            $backend = new AutoLoginBackend($backendConfig);
            $backend->setName($name);
            return $backend;
        }
        if ($backendConfig->resource === null) {
            throw new ConfigurationError(
                'Authentication configuration for backend "%s" is missing the resource directive',
                $name
            );
        }
        try {
            $resourceConfig = ResourceFactory::getResourceConfig($backendConfig->resource);
        } catch (ProgrammingError $e) {
            throw new ConfigurationError(
                'Resources not set up. Please contact your Icinga Web administrator'
            );
        }
        $resource = ResourceFactory::createResource($resourceConfig);
        switch ($backendType) {
            case 'db':
                $backend = new DbUserBackend($resource);
                break;
            case 'msldap':
                $groupOptions = array(
                    'group_base_dn'             => $backendConfig->group_base_dn,
                    'group_attribute'           => $backendConfig->group_attribute,
                    'group_member_attribute'    => $backendConfig->group_member_attribute,
                    'group_class'               => $backendConfig->group_class
                );
                $backend = new LdapUserBackend(
                    $resource,
                    $backendConfig->get('user_class', 'user'),
                    $backendConfig->get('user_name_attribute', 'sAMAccountName'),
                    $groupOptions
                );
                break;
            case 'ldap':
                if ($backendConfig->user_class === null) {
                    throw new ConfigurationError(
                        'Authentication configuration for backend "%s" is missing the user_class directive',
                        $name
                    );
                }
                if ($backendConfig->user_name_attribute === null) {
                    throw new ConfigurationError(
                        'Authentication configuration for backend "%s" is missing the user_name_attribute directive',
                        $name
                    );
                }
                $groupOptions = array(
                    'group_base_dn'             => $backendConfig->group_base_dn,
                    'group_attribute'           => $backendConfig->group_attribute,
                    'group_member_attribute'    => $backendConfig->group_member_attribute,
                    'group_class'               => $backendConfig->group_class
                );
                $backend = new LdapUserBackend(
                    $resource,
                    $backendConfig->user_class,
                    $backendConfig->user_name_attribute,
                    $groupOptions
                );
                break;
            default:
                throw new ConfigurationError(
                    'Authentication configuration for backend "%s" defines an invalid backend type.'
                    . ' Backend type "%s" is not supported',
                    $name,
                    $backendType
                );
        }
        $backend->setName($name);
        return $backend;
    }

    /**
     * Test whether the given user exists
     *
     * @param   User $user
     *
     * @return  bool
     */
    abstract public function hasUser(User $user);

    /**
     * Authenticate
     *
     * @param   User    $user
     * @param   string  $password
     *
     * @return  bool
     */
    abstract public function authenticate(User $user, $password);
}
