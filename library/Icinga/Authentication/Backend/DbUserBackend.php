<?php

// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication\Backend;

use \Zend_Db;
use \Icinga\User;
use \Icinga\Authentication\UserBackend;
use \Icinga\Authentication\Credentials;
use \Icinga\Authentication;
use \Icinga\Application\Logger;

/**
 * User authentication backend (@see Icinga\Authentication\UserBackend) for
 * authentication of users via an SQL database. The credentials needed to access
 * the database are configurable via the application.ini
 *
 * See the UserBackend class (@see Icinga\Authentication\UserBackend) for
 * usage information
 */
class DbUserBackend implements UserBackend
{
    /**
     * Mapping of all table column names
     */

    const USER_NAME_COLUMN   = 'username';

    const SALT_COLUMN        = 'salt';

    const PASSWORD_COLUMN    = 'password';

    const ACTIVE_COLUMN      = 'active';

    /**
     * The database connection that will be used for fetching users
     *
     * @var \Zend_Db
     */
    private $db = null;

    /**
     * The name of the user table
     *
     * @var String
     */
    private $userTable = "account";

    /**
     * Create a DbUserBackend
     *
     * @param   Zend_Db     The database that provides the authentication data
     */
    public function __construct($database)
    {
        $this->db = $database;

        // Test if the connection is available
        $this->db->getConnection();
    }

    /**
     * Check if the user identified by the given credentials is available
     *
     * @param Credentials $credentials  The login credentials
     *
     * @return boolean                  True when the username is known and currently active.
     */
    public function hasUsername(Credentials $credential)
    {
        if ($this->db === null) {
            Logger::warn('Ignoring hasUsername in database as no connection is available');
            return false;
        }
        $user = $this->getUserByName($credential->getUsername());
        return isset($user);
    }

    /**
     * Authenticate a user with the given credentials
     *
     * @param Credentials $credentials  The login credentials
     *
     * @return User|null                The authenticated user or Null.
     */
    public function authenticate(Credentials $credential)
    {
        if ($this->db === null) {
            Logger::warn('Ignoring database authentication as no connection is available');
            return null;
        }
        $this->db->getConnection();
        try {
            $salt = $this->getUserSalt($credential->getUsername());
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            return null;
        }
        $res = $this->db
            ->select()->from($this->userTable)
                ->where(self::USER_NAME_COLUMN.' = ?', $credential->getUsername())
                ->where(self::ACTIVE_COLUMN.   ' = ?', true)
                ->where(
                    self::PASSWORD_COLUMN. ' = ?',
                    hash_hmac(
                        'sha256',
                        $salt,
                        $credential->getPassword()
                    )
                )
                ->query()->fetch();
        if ($res !== false) {
            return $this->createUserFromResult($res);
        }
    }

    /**
     * Fetch the users salt from the database
     *
     * @param $username     The user whose salt should be fetched.
     *
     * @return String|null  Returns the salt-string or Null, when the user does not exist.
     */
    private function getUserSalt($username)
    {
        $this->db->getConnection();
        $res = $this->db->select()
            ->from($this->userTable, self::SALT_COLUMN)
            ->where(self::USER_NAME_COLUMN.' = ?', $username)
            ->query()->fetch();
        if ($res !== false) {
            return $res->{self::SALT_COLUMN};
        } else {
            throw new \Exception('No Salt found for user "' . $username . '"');
        }
    }

    /**
     * Fetch the user information from the database
     *
     * @param $username     The name of the user.
     *
     * @return User|null    Returns the user object, or null when the user does not exist.
     */
    private function getUserByName($username)
    {
        if ($this->db === null) {
            Logger::warn('Ignoring getUserByName as no database connection is available');
            return null;
        }
        try {
            $this->db->getConnection();
            $res = $this->db->
                select()->from($this->userTable)
                    ->where(self::USER_NAME_COLUMN.' = ?', $username)
                    ->where(self::ACTIVE_COLUMN.' = ?', true)
                    ->query()->fetch();
            if ($res !== false) {
                return $this->createUserFromResult($res);
            }
            return null;
        } catch (\Zend_Db_Statement_Exception $exc) {
            Logger::error("Could not fetch users from db : %s ", $exc->getMessage());
            return null;
        }
    }

    /**
     * Create a new instance of User from a query result
     *
     * @param $result   The query result containing the user row
     *
     * @return User     The created instance of User.
     */
    private function createUserFromResult($resultRow)
    {
        $usr = new User(
            $resultRow->{self::USER_NAME_COLUMN}
        );
        return $usr;
    }

    public function getUserCount()
    {

        $this->db->getConnection();
        $query = $this->db->select()->from($this->userTable, 'COUNT(*) as count')->query();
        return $query->fetch()->count;
    }
}
