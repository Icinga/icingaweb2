<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Exception\NotReadableError;
use Icinga\Data\ConfigObject;
use Icinga\User;
use Icinga\Util\String;

/**
 * Retrieve restrictions and permissions for users
 */
class AdmissionLoader
{

    /**
     * Get user permissions and restrictions
     *
     * @param   User $user
     *
     * @return  array
     */
    public function getPermissionsAndRestrictions(User $user)
    {
        $permissions = array();
        $restrictions = array();
        foreach ($user->getRoles() as $role) {
            $permissions = array_merge(
                $permissions,
                array_diff(String::trimSplit($role->permissions), $permissions)
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
        }
        return array($permissions, $restrictions);
    }
}
