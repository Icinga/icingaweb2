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
