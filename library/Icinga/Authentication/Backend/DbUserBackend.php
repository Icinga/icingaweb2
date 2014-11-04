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
     * The algorithm to use when hashing passwords
     *
     * @var string
     */
    const HASH_ALGORITHM = '$1$'; // MD5

    /**
     * The length of the salt to use when hashing a password
     *
     * @var int
     */
    const SALT_LENGTH = 12; // 12 is required by MD5

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
        $row = $select->from('icingaweb_user', array(new Zend_Db_Expr(1)))
            ->where('name = ?', $user->getUsername())
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
        $stmt = $this->conn->getDbAdapter()->prepare(
            'INSERT INTO icingaweb_user VALUES (:name, :active, :password_hash, now(), DEFAULT);'
        );
        $stmt->execute(array(
            ':name'             => $username,
            ':active'           => (int) $active,
            ':password_hash'    => $this->hashPassword($password)
        ));
    }

    /**
     * Fetch the row for the given user from the database
     *
     * @param   string      $username   The name of the user to fetch
     *
     * @return  stdClass|null           NULL in case the user does not exist
     */
    public function getUser($username)
    {
        $select = new Zend_Db_Select($this->conn->getConnection());
        $row = $select->from('icingaweb_user')->where('name = ?', $username)->query()->fetchObject();
        return $row === false ? null : $row;
    }

    /**
     * Authenticate the given user and return true on success, false on failure and throw an exception on error
     *
     * @param   User        $user
     * @param   string      $password
     *
     * @return  bool
     *
     * @throws  AuthenticationException
     */
    public function authenticate(User $user, $password)
    {
        try {
            $userData = $this->getUser($user->getUsername());
            if ($userData === null || ! $userData->active) {
                return false;
            }

            $hashToCompare = $this->hashPassword($password, $this->getSalt($userData->password_hash));
            return $hashToCompare === $userData->password_hash;
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
     * Extract salt from the given password hash
     *
     * @param   string  $hash   The hashed password
     *
     * @return  string
     */
    protected function getSalt($hash)
    {
        return substr($hash, strlen(self::HASH_ALGORITHM) + self::SALT_LENGTH);
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
        return openssl_random_pseudo_bytes(self::SALT_LENGTH);
    }

    /**
     * Hash a password
     *
     * @param   string  $password
     * @param   string  $salt
     *
     * @return  string
     */
    protected function hashPassword($password, $salt = null)
    {
        return crypt($password, self::HASH_ALGORITHM . ($salt !== null ? $salt : $this->generateSalt()));
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
            'icingaweb_user',
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
        $query = $this->conn->select()->from('icingaweb_user', array('name'));

        $users = array();
        foreach ($query->fetchAll() as $row) {
            $users[] = $row->name;
        }

        return $users;
    }
}
