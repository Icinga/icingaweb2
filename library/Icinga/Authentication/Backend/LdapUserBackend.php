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
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication\Backend;

use Icinga\Data\ResourceFactory;
use \stdClass;
use \Zend_Config;
use \Icinga\User;
use \Icinga\Authentication\UserBackend;
use \Icinga\Authentication\Credential;
use \Icinga\Protocol\Ldap;
use \Icinga\Protocol\Ldap\Connection as LdapConnection;
use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Exception\ConfigurationError;

/**
 * User authentication backend
 */
class LdapUserBackend implements UserBackend
{
    /**
     * Ldap resource
     *
     * @var Connection
     **/
    protected $connection;

    /**
     * The ldap connection information
     *
     * @var Zend_Config
     */
    private $config;

    /**
     * Name of the backend
     *
     * @var string
     */
    private $name;

    /**
     * Create a new LdapUserBackend
     *
     * @param Zend_Config   $config     The configuration for this authentication backend.
     *                                   'resource' => The name of the resource to use, or an actual
     *                                                  instance of \Icinga\Protocol\Ldap\Connection.
     *                                   'name'     => The name of this authentication backend.
     *
     * @throws ConfigurationError       When the given resource does not exist.
     */
    public function __construct(Zend_Config $config)
    {
        if (!isset($config->resource)) {
            throw new ConfigurationError('An authentication backend must provide a resource.');
        }
        $this->config = $config;
        $this->name = $config->name;
        if ($config->resource instanceof LdapConnection) {
            $this->connection = $config->resource;
        } else {
            $this->connection = ResourceFactory::createResource(
                ResourceFactory::getResourceConfig($config->resource)
            );
        }
    }

    /**
     * Name of the backend
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Test if the username exists
     *
     * @param   Credential $credential Credential to find user in database
     *
     * @return  bool
     */
    public function hasUsername(Credential $credential)
    {
        return $this->connection->fetchOne(
            $this->selectUsername($credential->getUsername())
        ) === $credential->getUsername();
    }

    /**
     * Removes the '*' character from $string
     *
     * @param string $string Input string
     *
     * @return string
     **/
    protected function stripAsterisks($string)
    {
        return str_replace('*', '', $string);
    }

    /**
     * Tries to fetch the username
     *
     * @param  string   $username The username to select
     *
     * @return stdClass $result
     **/
    protected function selectUsername($username)
    {
        return $this->connection->select()
            ->from(
                $this->config->user_class,
                array(
                    $this->config->user_name_attribute
                )
            )
            ->where(
                $this->config->user_name_attribute,
                $this->stripAsterisks($username)
            );
    }

    /**
     * Authenticate
     *
     * @param   Credential $credentials Credential to authenticate
     *
     * @return  User
     */
    public function authenticate(Credential $credentials)
    {
        if ($this->connection->testCredentials(
            $this->connection->fetchDN($this->selectUsername($credentials->getUsername())),
            $credentials->getPassword()
        )) {
            return new User($credentials->getUsername());
        }
    }

    /**
     * Return number of users in this backend
     *
     * @return  int The number of users set in this backend
     * @see     UserBackend::getUserCount
     */
    public function getUserCount()
    {
        return $this->connection->count(
            $this->connection->select()->from(
                $this->config->user_class,
                array(
                    $this->config->user_name_attribute
                )
            )
        );
    }

    /**
     *
     * Establish the connection to this authentication backend
     *
     * @throws \Exception   When the connection to the resource is not possible.
     */
    public function connect()
    {
        $this->connection->connect();
    }
}
