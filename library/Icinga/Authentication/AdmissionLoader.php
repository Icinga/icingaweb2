<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication;

use Generator;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotReadableError;
use Icinga\User;
use Icinga\Util\StringHelper;

/**
 * Retrieve restrictions and permissions for users
 */
class AdmissionLoader
{
    const LEGACY_PERMISSIONS = [
        'admin'                               => 'application/announcements',
        'application/stacktraces'             => 'user/application/stacktraces',
        'application/share/navigation'        => 'user/share/navigation',
        // Migrating config/application/* would include config/modules, so that's skipped
        //'config/application/*'                  => 'config/*',
        'config/application/general'          => 'config/general',
        'config/application/resources'        => 'config/resources',
        'config/application/navigation'       => 'config/navigation',
        'config/application/userbackend'      => 'config/access-control/users',
        'config/application/usergroupbackend' => 'config/access-control/groups',
        'config/authentication/*'             => 'config/access-control/*',
        'config/authentication/users/*'       => 'config/access-control/users',
        'config/authentication/users/show'    => 'config/access-control/users',
        'config/authentication/users/add'     => 'config/access-control/users',
        'config/authentication/users/edit'    => 'config/access-control/users',
        'config/authentication/users/remove'  => 'config/access-control/users',
        'config/authentication/groups/*'      => 'config/access-control/groups',
        'config/authentication/groups/show'   => 'config/access-control/groups',
        'config/authentication/groups/edit'   => 'config/access-control/groups',
        'config/authentication/groups/add'    => 'config/access-control/groups',
        'config/authentication/groups/remove' => 'config/access-control/groups',
        'config/authentication/roles/*'       => 'config/access-control/roles',
        'config/authentication/roles/show'    => 'config/access-control/roles',
        'config/authentication/roles/add'     => 'config/access-control/roles',
        'config/authentication/roles/edit'    => 'config/access-control/roles',
        'config/authentication/roles/remove'  => 'config/access-control/roles'
    ];

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
     * @param string       $username
     * @param array        $userGroups
     * @param ConfigObject $section
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
     * @param string       $name
     * @param ConfigObject $section
     *
     * @return Generator
     * @throws ConfigurationError
     */
    protected function loadRole($name, ConfigObject $section)
    {
        if (! isset($this->roles[$name])) {
            $permissions = $section->permissions ? StringHelper::trimSplit($section->permissions) : [];
            $refusals = $section->refusals ? StringHelper::trimSplit($section->refusals) : [];

            list($permissions, $newRefusals) = self::migrateLegacyPermissions($permissions);
            if (! empty($newRefusals)) {
                array_push($refusals, ...$newRefusals);
            }

            $restrictions = $section->toArray();
            unset($restrictions['users'], $restrictions['groups']);
            unset($restrictions['parent'], $restrictions['unrestricted']);
            unset($restrictions['refusals'], $restrictions['permissions']);

            $role = new Role();
            $this->roles[$name] = $role
                ->setName($name)
                ->setRefusals($refusals)
                ->setPermissions($permissions)
                ->setRestrictions($restrictions)
                ->setIsUnrestricted($section->get('unrestricted', false));

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
     * @param User $user
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
        $assignedRoles = [];
        $isUnrestricted = false;
        foreach ($this->roleConfig as $roleName => $roleConfig) {
            $assigned = $this->match($username, $userGroups, $roleConfig);
            if ($assigned) {
                $assignedRoles[] = $roleName;
            }

            if (! isset($roles[$roleName]) && $assigned) {
                foreach ($this->loadRole($roleName, $roleConfig) as $role) {
                    /** @var Role $role */
                    if (isset($roles[$role->getName()])) {
                        continue;
                    }

                    $roles[$role->getName()] = $role;

                    $permissions = array_merge(
                        $permissions,
                        array_diff($role->getPermissions(), $permissions)
                    );

                    $roleRestrictions = $role->getRestrictions();
                    foreach ($roleRestrictions as $name => & $restriction) {
                        $restriction = str_replace(
                            '$user.local_name$',
                            $user->getLocalUsername(),
                            $restriction
                        );
                        $restrictions[$name][] = $restriction;
                    }

                    $role->setRestrictions($roleRestrictions);

                    if (! $isUnrestricted) {
                        $isUnrestricted = $role->isUnrestricted();
                    }
                }
            }
        }

        Logger::debug(
            'Groups assigned for user "%s": %s',
            $user->getUsername(),
            join(', ', $assignedRoles)
        );

        $user->setAdditional('assigned_roles', $assignedRoles);

        $user->setIsUnrestricted($isUnrestricted);
        $user->setRestrictions($isUnrestricted ? [] : $restrictions);
        $user->setPermissions($permissions);
        $user->setRoles(array_values($roles));
    }

    public static function migrateLegacyPermissions(array $permissions)
    {
        $migratedGrants = [];
        $refusals = [];

        foreach ($permissions as $permission) {
            if (array_key_exists($permission, self::LEGACY_PERMISSIONS)) {
                $migratedGrants[] = self::LEGACY_PERMISSIONS[$permission];
            } elseif ($permission === 'no-user/password-change') {
                $refusals[] = 'user/password-change';
            } else {
                $migratedGrants[] = $permission;
            }
        }

        return [$migratedGrants, $refusals];
    }
}
