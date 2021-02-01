<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication;

use Generator;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotReadableError;
use Icinga\Data\ConfigObject;
use Icinga\User;
use Icinga\Util\StringHelper;

/**
 * Retrieve restrictions and permissions for users
 */
class AdmissionLoader
{
    /** @var Role[] */
    protected $roles;

    /** @var ConfigObject */
    protected $roleConfig;

    public function __construct()
    {
        try {
            $this->roleConfig = Config::app('roles');
        } catch (NotReadableError $e) {
            Logger::error('Can\'t access roles configuration. An exception was thrown:', $e);
        }
    }

    /**
     * Whether the user or groups are a member of the role
     *
     * @param   string          $username
     * @param   array           $userGroups
     * @param   ConfigObject    $section
     *
     * @return  bool
     */
    protected function match($username, $userGroups, ConfigObject $section)
    {
        $username = strtolower($username);
        if (! empty($section->users)) {
            $users = array_map('strtolower', StringHelper::trimSplit($section->users));
            if (in_array('*', $users)) {
                return true;
            }

            if (in_array($username, $users)) {
                return true;
            }
        }

        if (! empty($section->groups)) {
            $groups = array_map('strtolower', StringHelper::trimSplit($section->groups));
            foreach ($userGroups as $userGroup) {
                if (in_array(strtolower($userGroup), $groups)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Process role configuration and yield resulting roles
     *
     * This will also resolve any parent-child relationships.
     *
     * @param string $name
     * @param ConfigObject $section
     *
     * @return Generator
     * @throws ConfigurationError
     */
    protected function loadRole($name, ConfigObject $section)
    {
        if (! isset($this->roles[$name])) {
            $permissions = StringHelper::trimSplit($section->permissions);
            $refusals = StringHelper::trimSplit($section->refusals);

            $restrictions = $section->toArray();
            unset($restrictions['users'], $restrictions['groups']);
            unset($restrictions['refusals'], $restrictions['permissions']);

            $role = new Role();
            $this->roles[$name] = $role
                ->setName($name)
                ->setRefusals($refusals)
                ->setPermissions($permissions)
                ->setRestrictions($restrictions);

            if (isset($section->parent)) {
                $parentName = $section->parent;
                if (! $this->roleConfig->hasSection($parentName)) {
                    Logger::error(
                        'Failed to parse authentication configuration: Missing parent role "%s" (required by "%s")',
                        $parentName,
                        $name
                    );
                    throw new ConfigurationError(
                        t('Unable to parse authentication configuration. Check the log for more details.')
                    );
                }

                foreach ($this->loadRole($parentName, $this->roleConfig->getSection($parentName)) as $parent) {
                    if ($parent->getName() === $parentName) {
                        $role->setParent($parent);
                        $parent->addChild($role);

                        // Only yield main role once fully assembled
                        yield $role;
                    }

                    yield $parent;
                }
            } else {
                yield $role;
            }
        } else {
            yield $this->roles[$name];
        }
    }

    /**
     * Apply permissions, restrictions and roles to the given user
     *
     * @param   User    $user
     */
    public function applyRoles(User $user)
    {
        if ($this->roleConfig === null) {
            return;
        }

        $username = $user->getUsername();
        $userGroups = $user->getGroups();

        $roles = [];
        $permissions = [];
        $restrictions = [];
        foreach ($this->roleConfig as $roleName => $roleConfig) {
            if (! isset($roles[$roleName]) && $this->match($username, $userGroups, $roleConfig)) {
                foreach ($this->loadRole($roleName, $roleConfig) as $role) {
                    /** @var Role $role */
                    $roles[$role->getName()] = $role;

                    $permissions = array_merge(
                        $permissions,
                        array_diff($role->getPermissions(), $permissions)
                    );

                    $roleRestrictions = $role->getRestrictions();
                    foreach ($roleRestrictions as $name => & $restriction) {
                        $restriction = str_replace('$user:local_name$', $user->getLocalUsername(), $restriction);
                        $restrictions[$name][] = $restriction;
                    }

                    $role->setRestrictions($roleRestrictions);
                }
            }
        }

        $user->setRestrictions($restrictions);
        $user->setPermissions($permissions);
        $user->setRoles(array_values($roles));
    }
}
