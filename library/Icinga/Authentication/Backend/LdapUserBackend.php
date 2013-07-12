<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @author Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication\Backend;

use Icinga\Authentication\User as User;
use Icinga\Authentication\UserBackend;
use Icinga\Authentication\Credentials;
use Icinga\Protocol\Ldap;
use Icinga\Application\Config as IcingaConfig;

/**
*   User authentication backend (@see Icinga\Authentication\UserBackend) for
*   authentication of users via LDAP. The attributes and location of the
*   user is configurable via the application.ini
*
*   See the UserBackend class (@see Icinga\Authentication\UserBackend) for
*   usage information
**/
class LdapUserBackend implements UserBackend
{
    /**
    *   @var Ldap\Connection
    **/
    protected $connection;

    /**
    *   Creates a new Authentication backend using the
    *   connection information provided in $config
    *
    *   @param object $config   The ldap connection information
    **/
    public function __construct($config)
    {
        $this->connection = new Ldap\Connection($config);
    }

    /**
    *   @see Icinga\Authentication\UserBackend::hasUsername
    **/
    public function hasUsername(Credentials $credential)
    {
        return $this->connection->fetchOne(
            $this->selectUsername($credential->getUsername())
        ) === $credential->getUsername();
    }

    /**
    *   Removes the '*' characted from $string
    *
    *   @param String $string
    *
    *   @return String
    **/
    protected function stripAsterisks($string)
    {
        return str_replace('*', '', $string);
    }

    /**
    *   Tries to fetch the username given in $username from
    *   the ldap connection, using the configuration parameters
    *   given in the Authentication configuration
    *
    *   @param  String  $username       The username to select
    *
    *   @return object  $result
    **/
    protected function selectUsername($username)
    {
        return $this->connection->select()
            ->from(IcingaConfig::app('authentication')->users->user_class,
                array(IcingaConfig::app('authentication')->users->user_name_attribute))
            ->where(IcingaConfig::app('authentication')->users->user_name_attribute,
                $this->stripAsterisks($username));
    }

    /**
    *   @see Icinga\Authentication\UserBackend::authenticate
    **/
    public function authenticate(Credentials $credentials)
    {
        if (!$this->connection->testCredentials(
            $this->connection->fetchDN($this->selectUsername($credentials->getUsername())),
            $credentials->getPassword()
        )
        ) {
            return false;
        }
        $user = new User($credentials->getUsername());

        return $user;
    }
}
