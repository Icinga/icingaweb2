<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\User;

use Exception;
use PDO;
use Icinga\Exception\AuthenticationException;
use Icinga\Repository\DbRepository;
use Icinga\User;

class DbUserBackend extends DbRepository implements UserBackendInterface
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
     * The query columns being provided
     *
     * @var array
     */
    protected $queryColumns = array(
        'user' => array(
            'user'          => 'name COLLATE utf8_general_ci',
            'user_name'     => 'name',
            'is_active'     => 'active',
            'created_at'    => 'UNIX_TIMESTAMP(ctime)',
            'last_modified' => 'UNIX_TIMESTAMP(mtime)'
        )
    );

    /**
     * The columns which are not permitted to be queried
     *
     * @var array
     */
    protected $filterColumns = array('user');

    /**
     * The default sort rules to be applied on a query
     *
     * @var array
     */
    protected $sortRules = array(
        'user_name' => array(
            'columns'   => array(
                'user_name asc',
                'is_active desc'
            )
        )
    );

    /**
     * Initialize this database user backend
     */
    protected function init()
    {
        if (! $this->ds->getTablePrefix()) {
            $this->ds->setTablePrefix('icingaweb_');
        }
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

        $stmt = $this->ds->getDbAdapter()->prepare(
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
        if ($this->ds->getDbType() === 'pgsql') {
            // Since PostgreSQL version 9.0 the default value for bytea_output is 'hex' instead of 'escape'
            $stmt = $this->ds->getDbAdapter()->prepare(
                'SELECT ENCODE(password_hash, \'escape\') FROM icingaweb_user WHERE name = :name AND active = 1'
            );
        } else {
            $stmt = $this->ds->getDbAdapter()->prepare(
                'SELECT password_hash FROM icingaweb_user WHERE name = :name AND active = 1'
            );
        }

        $stmt->execute(array(':name' => $username));
        $stmt->bindColumn(1, $lob, PDO::PARAM_LOB);
        $stmt->fetch(PDO::FETCH_BOUND);
        if (is_resource($lob)) {
            $lob = stream_get_contents($lob);
        }

        return $this->ds->getDbType() === 'pgsql' ? pg_unescape_bytea($lob) : $lob;
    }

    /**
     * Authenticate the given user
     *
     * @param   User        $user
     * @param   string      $password
     *
     * @return  bool                        True on success, false on failure
     *
     * @throws  AuthenticationException     In case authentication is not possible due to an error
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
}
