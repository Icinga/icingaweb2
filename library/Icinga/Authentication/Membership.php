<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}}

namespace Icinga\Authentication;

use Icinga\Application\Config;
use Icinga\Exception\NotReadableError;
use Icinga\Util\String;

/**
 * Retrieve membership information for users and group
 */
class Membership
{
    /**
     * Return a list of groups for an username
     *
     * @param   string  $username
     *
     * @return  array
     */
    public function getGroupsByUsername($username)
    {
        $groups = array();
        try {
            $config = Config::app('memberships');
        } catch (NotReadableError $e) {
            return $groups;
        }
        foreach ($config as $section) {
            $users = String::trimSplit($section->users);
            if (in_array($username, $users)) {
                $groups = array_merge($groups, String::trimSplit($section->groups));
            }
        }
        return $groups;
    }
}
