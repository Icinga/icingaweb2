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

use \Exception;
use Icinga\User;
use Icinga\Authentication\UserBackend;
use Icinga\Protocol\Ldap\Connection;
use Icinga\Exception\AuthenticationException;

class LdapUserBackend extends UserBackend
{
    /**
     * Connection to the LDAP server
     *
     * @var Connection
     **/
    protected $conn;

    protected $userClass;

    protected $userNameAttribute;

    public function __construct(Connection $conn, $userClass, $userNameAttribute)
    {
        $this->conn = $conn;
        $this->userClass = $userClass;
        $this->userNameAttribute = $userNameAttribute;
    }

    /**
     * Create query
     *
     * @param   string $username
     *
     * @return  \Icinga\Protocol\Ldap\Query
     **/
    protected function createQuery($username)
    {
        return $this->conn->select()
            ->from(
                $this->userClass,
                array($this->userNameAttribute)
            )
            ->where(
                $this->userNameAttribute,
                str_replace('*', '', $username)
            );
    }

    /**
     * Probe the backend to test if authentication is possible
     *
     * Try to bind to the backend and query all available users to check if:
     * <ul>
     *  <li>User connection credentials are correct and the bind is possible</li>
     *  <li>At least one user exists</li>
     *  <li>The specified userClass has the property specified by userNameAttribute</li>
     * </ul>
     *
     * @throws AuthenticationException  When authentication is not possible
     */
    public function assertAuthenticationPossible()
    {
        $q = $this->conn->select()->from($this->userClass);
        $result = $q->fetchRow();
        if (!isset($result)) {
            throw new AuthenticationException(
                sprintf('No objects with objectClass="%s" in DN="%s" found.',
                $this->userClass,
                $this->conn->getDN()
            ));
        }

        if (!isset($result->{$this->userNameAttribute})) {
            throw new AuthenticationException(
                sprintf('UserNameAttribute "%s" not existing in objectClass="%s"',
                    $this->userNameAttribute,
                    $this->userClass
            ));
        }
    }

    /**
     * Test whether the given user exists
     *
     * @param   User $user
     *
     * @return  bool
     * @throws  AuthenticationException
     */
    public function hasUser(User $user)
    {
        $username = $user->getUsername();
        return $this->conn->fetchOne($this->createQuery($username)) === $username;
    }

    /**
     * Authenticate the given user and return true on success, false on failure and null on error
     *
     * @param   User    $user
     * @param   string  $password
     * @param   boolean $healthCheck        Perform additional health checks to generate more useful exceptions in case
     *                                      of a configuration or backend error
     *
     * @return  bool                        True when the authentication was successful, false when the username
     *                                      or password was invalid
     * @throws  AuthenticationException     When an error occurred during authentication and authentication is not possible
     */
    public function authenticate(User $user, $password, $healthCheck = true)
    {
        if ($healthCheck) {
            try {
                $this->assertAuthenticationPossible();
            } catch (AuthenticationException $e) {
                // Authentication not possible
                throw new AuthenticationException(
                    sprintf(
                        'Authentication against backend "%s" not possible: ',
                        $this->getName()
                    ),
                    0,
                    $e
                );
            }
        }
        if (! $this->hasUser($user)) {
            return false;
        }
        try {
            return $this->conn->testCredentials(
                $this->conn->fetchDN($this->createQuery($user->getUsername())),
                $password
            );
        } catch (\Exception $e) {
            // Error during authentication of this specific user
            throw new AuthenticationException(
                sprintf(
                    'Failed to authenticate user "%s" against backend "%s". An exception was thrown:',
                    $user->getUsername(),
                    $this->getName()
                ),
                0,
                $e
            );
        }
    }

    /**
     * Get the number of users available
     *
     * @return int
     */
    public function count()
    {

        return $this->conn->count(
            $this->conn->select()->from(
                $this->userClass,
                array(
                    $this->userNameAttribute
                )
            )
        );
    }
}

