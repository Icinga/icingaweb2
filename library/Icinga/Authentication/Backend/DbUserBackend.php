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
use Icinga\Exception\IcingaException;

class DbUserBackend extends UserBackend
{
    /**
     * Connection to the database
     *
     * @var DbConnection
     */
    protected $conn;

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
     * Add a new user
     *
     * @param   string  $username   The name of the new user
     * @param   string  $password   The new user's password
     * @param   bool    $active     Whether the user is active
     */
    public function addUser($username, $password, $active = true)
    {
        $passwordSalt = $this->generateSalt();
        $hashedPassword = $this->hashPassword($password, $passwordSalt);
        $stmt = $this->conn->getDbAdapter()->prepare(
            'INSERT INTO account VALUES (:username, :salt, :password, :active);'
        );
        $stmt->execute(array(
            ':active'   => $active,
            ':username' => $username,
            ':salt'     => $passwordSalt,
            ':password' => $hashedPassword
        ));
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
                throw new IcingaException(
                    'Cannot find salt for user %s',
                    $user->getUsername()
                );
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
                'Failed to authenticate user "%s" against backend "%s". An exception was thrown:',
                $user->getUsername(),
                $this->getName(),
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
    protected function getSalt($username)
    {
        $select = new Zend_Db_Select($this->conn->getConnection());
        $row = $select->from('account', array('salt'))->where('username = ?', $username)->query()->fetchObject();
        return ($row !== false) ? $row->salt : null;
    }

    /**
     * Return a random salt
     *
     * The returned salt is safe to be used for hashing a user's password
     *
     * @return  string
     */
    protected function generateSalt()
    {
        return openssl_random_pseudo_bytes(64);
    }

    /**
     * Hash a password
     *
     * @param   string $password
     * @param   string $salt
     *
     * @return  string
     */
    protected function hashPassword($password, $salt) {
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

    /**
     * Return the names of all available users
     *
     * @return  array
     */
    public function listUsers()
    {
        $query = $this->conn->select()->from('account', array('username'));

        $users = array();
        foreach ($query->fetchAll() as $row) {
            $users[] = $row->username;
        }

        return $users;
    }
}
