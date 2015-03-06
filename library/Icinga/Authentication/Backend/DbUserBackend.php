<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\Backend;

use PDO;
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
        $select = new Zend_Db_Select($this->conn->getDbAdapter());
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
        $passwordHash = $this->hashPassword($password);

        $stmt = $this->conn->getDbAdapter()->prepare(
            'INSERT INTO icingaweb_user VALUES (:name, :active, :password_hash, now(), DEFAULT);'
        );
        $stmt->bindParam(':name', $username, PDO::PARAM_STR);
        $stmt->bindParam(':active', $active, PDO::PARAM_INT);
        $stmt->bindParam(':password_hash', $passwordHash, PDO::PARAM_LOB);
        $stmt->execute();
    }

    /**
     * Fetch the hashed password for the given user
     *
     * @param   string  $username   The name of the user
     *
     * @return  string
     */
    protected function getPasswordHash($username)
    {
        if ($this->conn->getDbType() === 'pgsql') {
            // Since PostgreSQL version 9.0 the default value for bytea_output is 'hex' instead of 'escape'
            $stmt = $this->conn->getDbAdapter()->prepare(
                'SELECT ENCODE(password_hash, \'escape\') FROM icingaweb_user WHERE name = :name AND active = 1'
            );
        } else {
            $stmt = $this->conn->getDbAdapter()->prepare(
                'SELECT password_hash FROM icingaweb_user WHERE name = :name AND active = 1'
            );
        }

        $stmt->execute(array(':name' => $username));
        $stmt->bindColumn(1, $lob, PDO::PARAM_LOB);
        $stmt->fetch(PDO::FETCH_BOUND);
        if (is_resource($lob)) {
            $lob = stream_get_contents($lob);
        }

        return $this->conn->getDbType() === 'pgsql' ? pg_unescape_bytea($lob) : $lob;
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
            $passwordHash = $this->getPasswordHash($user->getUsername());
            $passwordSalt = $this->getSalt($passwordHash);
            $hashToCompare = $this->hashPassword($password, $passwordSalt);
            return $hashToCompare === $passwordHash;
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
        return substr($hash, strlen(self::HASH_ALGORITHM), self::SALT_LENGTH);
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
        $select = new Zend_Db_Select($this->conn->getDbAdapter());
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
