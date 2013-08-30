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

use \Exception;
use \stdClass;
use \Zend_Config;
use \Zend_Db;
use \Zend_Db_Adapter_Abstract;
use \Icinga\Application\DbAdapterFactory;
use \Icinga\Exception\ProgrammingError;
use \Icinga\User;
use \Icinga\Authentication\UserBackend;
use \Icinga\Authentication\Credential;
use \Icinga\Authentication;
use \Icinga\Application\Logger;
use \Icinga\Exception\ConfigurationError;

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
     * Table map for column username
     *
     * @var string
     */
    const USER_NAME_COLUMN = 'username';

    /**
     * Table map for column salt
     *
     * @var string
     */
    const SALT_COLUMN = 'salt';

    /**
     * Table map for column password
     *
     * @var string
     */
    const PASSWORD_COLUMN = 'password';

    /**
     * Table map for column active
     *
     * @var string
     */
    const ACTIVE_COLUMN = 'active';

    /**
     * The database connection that will be used for fetching users
     *
     * @var Zend_Db
     */
    private $db;

    /**
     * The name of the user table
     *
     * @var String
     */
    private $userTable = 'account';

    /**
     * Name of the backend
     *
     * @var string
     */
    private $name;

    /**
     * Create a DbUserBackend
     *
     * @param   Zend_Config $config The database that provides the authentication data
     * @throws  ConfigurationError
     */
    public function __construct(Zend_Config $config)
    {
        $this->name = $config->name;

        if ($config->resource instanceof Zend_Db_Adapter_Abstract) {
            $this->db = $config->resource;
        } else {
            $this->db = DbAdapterFactory::getDbAdapter($config->resource);
        }

        // Throw any errors for Authentication/Manager
        $this->db->getConnection();
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
     * Check if the user identified by the given credentials is available
     *
     * @param   Credential $credential  Credential to find a user in the database
     *
     * @return  boolean                 True when the username is known and currently active.
     */
    public function hasUsername(Credential $credential)
    {
        $user = $this->getUserByName($credential->getUsername());
        return isset($user);
    }

    /**
     * Authenticate a user with the given credentials
     *
     * @param   Credential $credential      Credential to authenticate
     *
     * @return  User|null                   The authenticated user or Null.
     */
    public function authenticate(Credential $credential)
    {
        try {
            $salt = $this->getUserSalt($credential->getUsername());
        } catch (Exception $e) {
            Logger::error(
                'Could not fetch salt from database for user %s. Exception was thrown: %s',
                $credential->getUsername(),
                $e->getMessage()
            );
            return null;
        }
        $res = $this->db
            ->select()->from($this->userTable)
                ->where(self::USER_NAME_COLUMN . ' = ?', $credential->getUsername())
                ->where(self::ACTIVE_COLUMN . ' = ?', true)
                ->where(
                    self::PASSWORD_COLUMN . ' = ?',
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

        return null;
    }

    /**
     * Fetch the users salt from the database
     *
     * @param   string$username     The user whose salt should be fetched
     *
     * @return  string|null         Return the salt-string or null, when the user does not exist
     * @throws  ProgrammingError
     */
    private function getUserSalt($username)
    {
        $res = $this->db->select()
            ->from($this->userTable, self::SALT_COLUMN)
            ->where(self::USER_NAME_COLUMN.' = ?', $username)
            ->query()->fetch();
        if ($res !== false) {
            return $res->{self::SALT_COLUMN};
        } else {
            throw new ProgrammingError('No Salt found for user "' . $username . '"');
        }
    }

    /**
     * Fetch the user information from the database
     *
     * @param   string  $username   The name of the user
     *
     * @return  User|null           Returns the user object, or null when the user does not exist
     */
    private function getUserByName($username)
    {
        $this->db->getConnection();
        $res = $this->db->
            select()->from($this->userTable)
                ->where(self::USER_NAME_COLUMN .' = ?', $username)
                ->where(self::ACTIVE_COLUMN .' = ?', true)
                ->query()->fetch();
        if ($res !== false) {
            return $this->createUserFromResult($res);
        }
        return null;

    }

    /**
     * Create a new instance of User from a query result
     *
     * @param   stdClass $resultRow     Result object from database
     *
     * @return  User                    The created instance of User.
     */
    private function createUserFromResult(stdClass $resultRow)
    {
        $usr = new User(
            $resultRow->{self::USER_NAME_COLUMN}
        );
        return $usr;
    }

    /**
     * Return the number of users in this database connection
     *
     * This class is mainly used for determining whether the authentication backend is valid or not
     *
     * @return  int The number of users set in this backend
     * @see     UserBackend::getUserCount
     */
    public function getUserCount()
    {
        $query = $this->db->select()->from($this->userTable, 'COUNT(*) as count')->query();
        return $query->fetch()->count;
    }
}
