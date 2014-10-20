<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}}

namespace Icinga\Authentication;

use Icinga\Application\Config;
use Icinga\Exception\NotReadableError;
use Icinga\User;
use Icinga\Util\String;

/**
 * Retrieve restrictions and permissions for users
 */
class AdmissionLoader
{
    /**
     * @param   string  $username
     * @param   array   $userGroups
     * @param   mixed   $section
     *
     * @return  bool
     */
    protected function match($username, $userGroups, $section)
    {
        $username = strtolower($username);
        if (! empty($section->users)) {
            $users = array_map('strtolower', String::trimSplit($section->users));
            if (in_array($username, $users)) {
                return true;
            }
        }
        if (! empty($section->groups)) {
            $groups = array_map('strtolower', String::trimSplit($section->groups));
            foreach ($userGroups as $userGroup) {
                if (in_array(strtolower($userGroup), $groups)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get user permissions
     *
     * @param   User  $user
     *
     * @return  array
     */
    public function getPermissions(User $user)
    {
        $permissions = array();
        try {
            $config = Config::app('permissions');
        } catch (NotReadableError $e) {
            Logger::error(
                'Can\'t get permissions for user \'%s\'. An exception was thrown:',
                $user->getUsername(),
                $e
            );
            return $permissions;
        }
        $username = $user->getUsername();
        $userGroups = $user->getGroups();
        foreach ($config as $section) {
            if (! empty($section->permissions)
                && $this->match($username, $userGroups, $section)
            ) {
                $permissions = array_merge(
                    $permissions,
                    array_diff(String::trimSplit($section->permissions), $permissions)
                );
            }
        }
        return $permissions;
    }

    /**
     * Get user restrictions
     *
     * @param   User  $user
     *
     * @return  array
     */
    public function getRestrictions(User $user)
    {
        $restrictions = array();
        try {
            $config = Config::app('restrictions');
        } catch (NotReadableError $e) {
            Logger::error(
                'Can\'t get restrictions for user \'%s\'. An exception was thrown:',
                $user->getUsername(),
                $e
            );
            return $restrictions;
        }
        $username = $user->getUsername();
        $userGroups = $user->getGroups();
        foreach ($config as $section) {
            if (! empty($section->restriction)
                && $this->match($username, $userGroups, $section)
            ) {
                $restrictions = array_merge(
                    $restrictions,
                    array_diff(String::trimSplit($section->restriction), $restrictions)
                );
            }
        }
        return $restrictions;
    }
}
