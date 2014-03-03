<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
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
