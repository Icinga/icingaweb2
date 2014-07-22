<?php
// {{{ICINGA_LICENSE_HEADER}}}
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