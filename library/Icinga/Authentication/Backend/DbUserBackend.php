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

use Icinga\Authentication\UserBackend;
use Icinga\Data\Db\DbConnection;
use Icinga\User;
use Icinga\Exception\AuthenticationException;
use Exception;
use Zend_Db_Expr;
use Zend_Db_Select;

class DbUserBackend extends UserBackend
{
    /**
     * Connection to the database
     *
     * @var DbConnection
     */
    private $conn;

    public function __construct(DbConnection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Test whether the given user exists
     *
     * @param   User $user
     *
     * @return  bool
     */
    public function hasUser(User $user)
    {
        $select = new Zend_Db_Select($this->conn->getConnection());
        $row = $select->from('account', array(new Zend_Db_Expr(1)))
            ->where('username = ?', $user->getUsername())
            ->query()->fetchObject();

        return ($row !== false) ? true : false;
    }

    /**
     * Authenticate the given user and return true on success, false on failure and null on error
     *
     * @param   User        $user
     * @param   string      $password
     *
     * @return  bool|null
     * @throws  AuthenticationException
     */
    public function authenticate(User $user, $password)
    {
        try {
            $salt = $this->getSalt($user->getUsername());
            if ($salt === null) {
                return false;
            }
            if ($salt === '') {
                throw new Exception('Cannot find salt for user ' . $user->getUsername());
            }

            $select = new Zend_Db_Select($this->conn->getConnection());
            $row = $select->from('account', array(new Zend_Db_Expr(1)))
                ->where('username = ?', $user->getUsername())
                ->where('active = ?', true)
                ->where('password = ?', $this->hashPassword($password, $salt))
                ->query()->fetchObject();

            return ($row !== false) ? true : false;
        } catch (Exception $e) {
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
     * Get salt by username
     *
     * @param   string $username
     *
     * @return  string|null
     */
    private function getSalt($username)
    {
        $select = new Zend_Db_Select($this->conn->getConnection());
        $row = $select->from('account', array('salt'))->where('username = ?', $username)->query()->fetchObject();
        return ($row !== false) ? $row->salt : null;
    }

    /**
     * Hash a password
     *
     * @param   string $password
     * @param   string $salt
     *
     * @return  string
     */
    private function hashPassword($password, $salt) {
        return hash_hmac('sha256', $password, $salt);
    }

    /**
     * Get the number of users available
     *
     * @return int
     */
    public function count()
    {
        $select = new Zend_Db_Select($this->conn->getConnection());
        $row = $select->from(
            'account',
            array('count' => 'COUNT(*)')
        )->query()->fetchObject();

        return ($row !== false) ? $row->count : 0;
    }
}