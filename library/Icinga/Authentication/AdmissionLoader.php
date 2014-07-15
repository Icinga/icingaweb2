<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}}

namespace Icinga\Authentication;

use Icinga\Application\Config;
use Icinga\Exception\NotReadableError;
use Icinga\Util\String;

/**
 * Retrieve restrictions and permissions for users
 */
class AdmissionLoader
{
    /**
     * Match against groups
     *
     * @param   string  $section
     * @param   string  $username
     * @param   array   $groups
     *
     * @return  bool
     */
    private function match($section, $username, array $groups)
    {
        if ($section->users && in_array($username, String::trimSplit($section->users)) === true) {
            return true;
        }

        if ($section->groups && count(array_intersect(String::trimSplit($section->groups), $groups)) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve permissions
     *
     * @param   string  $username
     * @param   array   $groups
     *
     * @return  array
     */
    public function getPermissions($username, array $groups)
    {
        $permissions = array();
        try {
            $config = Config::app('permissions');
        } catch (NotReadableError $e) {
            return $permissions;
        }
        foreach ($config as $section) {
            if ($this->match($section, $username, $groups)) {
                foreach ($section as $key => $value) {
                    if (strpos($key, 'permission') === 0) {
                        $permissions = array_merge($permissions, String::trimSplit($value));
                    }
                }
            }
        }
        return $permissions;
    }

    /**
     * Retrieve restrictions
     *
     * @param   $username
     * @param   array $groups
     *
     * @return  array
     */
    public function getRestrictions($username, array $groups)
    {
        $restrictions = array();
        try {
            $config = Config::app('restrictions');
        } catch (NotReadableError $e) {
            return $restrictions;
        }
        foreach ($config as $section) {
            if ($this->match($section, $username, $groups)) {
                if (!array_key_exists($section->name, $restrictions)) {
                    $restrictions[$section->name] = array();
                }
                $restrictions[$section->name][] = $section->restriction;
            }
        }
        return $restrictions;
    }
}
