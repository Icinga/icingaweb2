<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Authentication\Role;
use Icinga\Exception\NotReadableError;
use Icinga\Data\ConfigObject;
use Icinga\User;
use Icinga\Util\StringHelper;

/**
 * Retrieve restrictions and permissions for users
 */
class AdmissionLoader
{
    /**
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
     * Apply permissions, restrictions and roles to the given user
     *
     * @param   User    $user
     */
    public function applyRoles(User $user)
    {
        $username = $user->getUsername();
        try {
            $roles = Config::app('roles');
        } catch (NotReadableError $e) {
            Logger::error(
                'Can\'t get permissions and restrictions for user \'%s\'. An exception was thrown:',
                $username,
                $e
            );
            return;
        }
        $userGroups = $user->getGroups();
        $permissions = array();
        $restrictions = array();
        $roleObjs = array();
        foreach ($roles as $roleName => $role) {
            if ($this->match($username, $userGroups, $role)) {
                $permissionsFromRole = StringHelper::trimSplit($role->permissions);
                $permissions = array_merge(
                    $permissions,
                    array_diff($permissionsFromRole, $permissions)
                );
                $restrictionsFromRole = $role->toArray();
                unset($restrictionsFromRole['users']);
                unset($restrictionsFromRole['groups']);
                unset($restrictionsFromRole['permissions']);
                foreach ($restrictionsFromRole as $name => $restriction) {
                    if (! isset($restrictions[$name])) {
                        $restrictions[$name] = array();
                    }
                    $restrictions[$name][] = $restriction;
                }

                $roleObj = new Role();
                $roleObjs[] = $roleObj
                    ->setName($roleName)
                    ->setPermissions($permissionsFromRole)
                    ->setRestrictions($restrictionsFromRole);
            }
        }
        $user->setPermissions($permissions);
        $user->setRestrictions($restrictions);
        $user->setRoles($roleObjs);
    }
}
