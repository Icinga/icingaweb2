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
use \stdClass;
use \Zend_Config;
use \Zend_Db;
use \Zend_Db_Adapter_Abstract;
use \Icinga\Data\ResourceFactory;
use \Icinga\User;
use \Icinga\Authentication\UserBackend;
use \Icinga\Authentication\Credential;
use \Icinga\Authentication;
use \Icinga\Application\Logger;
use \Icinga\Exception\ProgrammingError;
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
     * Column name to identify active users
     *
     * @var string
     */
    private $activeColumnName = 'active';

    /**
     * Column name to fetch the password
     *
     * @var string
     */
    private $passwordColumnName = 'password';

    /**
     * Column name for password salt
     *
     * @var string
     */
    private $saltColumnName = 'salt';

    /**
     * Column name for user name
     *
     * @var string
     */
    private $userColumnName = 'username';

    /**
     * Column name of email
     *
     * @var string
     */
    private $emailColumnName = null;

    /**
     * Name of the backend
     *
     * @var string
     */
    private $name;

    /**
     * Create a new DbUserBackend
     *
     * @param Zend_Config   $config      The configuration for this authentication backend.
     *                                    'resource' => The name of the resource to use, or an actual
     *                                                  instance of Zend_Db_Adapter_Abstract
     *                                    'name'     => The name of this authentication backend
     *
     * @throws ConfigurationError        When the given resource does not exist.
     */
    public function __construct(Zend_Config $config)
    {
        if (!isset($config->resource)) {
            throw new ConfigurationError('An authentication backend must provide a resource.');
        }
        $this->name = $config->name;
        if ($config->resource instanceof Zend_Db_Adapter_Abstract) {
            $this->db = $config->resource;
        } else {
            $resource = ResourceFactory::createResource(ResourceFactory::getResourceConfig($config->resource));
            $this->db = $resource->getConnection();
        }
    }

    /**
     * Setter for password column
     *
     * @param string $passwordColumnName
     */
    public function setPasswordColumnName($passwordColumnName)
    {
        $this->passwordColumnName = $passwordColumnName;
    }

    /**
     * Setter for password salt column
     *
     * @param string $saltColumnName
     */
    public function setSaltColumnName($saltColumnName)
    {
        $this->saltColumnName = $saltColumnName;
    }

    /**
     * Setter for usernamea column
     *
     * @param string $userColumnName
     */
    public function setUserColumnName($userColumnName)
    {
        $this->userColumnName = $userColumnName;
    }

    /**
     * Setter for database table
     *
     * @param String $userTable
     */
    public function setUserTable($userTable)
    {
        $this->userTable = $userTable;
    }

    /**
     * Setter for column identifying an active user
     *
     * Set this to null if no active column exists.
     *
     * @param string $activeColumnName
     */
    public function setActiveColumnName($activeColumnName)
    {
        $this->activeColumnName = $activeColumnName;
    }

    /**
     * Setter for email column
     *
     * Set to null if not needed
     *
     * @param string $emailColumnName
     */
    public function setEmailColumnName($emailColumnName)
    {
        $this->emailColumnName = $emailColumnName;
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
        $sth = $this->db
            ->select()->from($this->userTable)
            ->where($this->userColumnName . ' = ?', $credential->getUsername())
            ->where(
                $this->passwordColumnName . ' = ?',
                $this->createPasswordHash($credential->getPassword(), $salt)
            );

        if ($this->activeColumnName !== null) {
            $sth->where($this->activeColumnName . ' = ?', true);
        }

        $res = $sth->query()->fetch();

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
            ->from($this->userTable, $this->saltColumnName)
            ->where($this->userColumnName . ' = ?', $username)
            ->query()->fetch();
        if ($res !== false) {
            return $res->{$this->saltColumnName};
        } else {
            throw new ProgrammingError('No Salt found for user "' . $username . '"');
        }
    }

    /**
     * Create password hash at this place
     *
     * @param   string $password
     * @param   string $salt
     *
     * @return  string
     */
    protected function createPasswordHash($password, $salt) {
        return hash_hmac('sha256', $password, $salt);
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
        $sth = $this->db->select()
            ->from($this->userTable)
            ->where($this->userColumnName .' = ?', $username);

        if ($this->activeColumnName !== null) {
            $sth->where($this->activeColumnName .' = ?', true);
        }

        $res = $sth->query()->fetch();

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
    protected function createUserFromResult(stdClass $resultRow)
    {
        $usr = new User(
            $resultRow->{$this->userColumnName},
            null,
            null,
            (isset($resultRow->{$this->emailColumnName})) ? $resultRow->{$this->emailColumnName} : null
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

    /**
     * Try to connect to the underlying database.
     *
     * @throws ConfigurationError   When the backend is not reachable with the given configuration.
     */
    public function connect()
    {
        $this->db->getConnection();
    }
}
