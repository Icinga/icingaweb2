<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Config;
use Icinga\Authentication\Auth;
use Icinga\Crypt\AesCrypt;
use Icinga\Common\Database;
use Icinga\User;
use ipl\Sql\Expression;
use ipl\Sql\Select;
use RuntimeException;
use UnexpectedValueException;

/**
 * Remember me component
 *
 * Retains credentials for 30 days by default in order to stay signed in even after the session is closed.
 */
class RememberMe
{
    use Database;

    /** @var string Cookie name */
    const COOKIE = 'icingaweb2-remember-me';

    /** @var string Database table name */
    const TABLE = 'icingaweb_rememberme';

    /** @var string Encrypted password of the user */
    protected $encryptedPassword;

    /** @var string */
    protected $username;

    /** @var AesCrypt Instance for encrypting/decrypting the credentials */
    protected $aesCrypt;

    /** @var int Timestamp when the remember me cookie expires */
    protected $expiresAt;

    /**
     * Get whether the remember cookie is set
     *
     * @return bool
     */
    public static function hasCookie()
    {
        return isset($_COOKIE[static::COOKIE]);
    }

    /**
     * Unset the remember me cookie from PHP's `$_COOKIE` superglobal and return the invalidation cookie
     *
     * @return Cookie Cookie which has to be sent to client in oder to remove the remember me cookie
     */
    public static function forget()
    {
        unset($_COOKIE[static::COOKIE]);

        return (new Cookie(static::COOKIE))
            ->setHttpOnly(true)
            ->forgetMe();
    }

    /**
     * Create the remember me component from the remember me cookie
     *
     * @return static
     */
    public static function fromCookie()
    {
        $data = explode('|', $_COOKIE[static::COOKIE]);
        $iv = base64_decode(array_pop($data));

        $select = (new Select())
            ->from(static::TABLE)
            ->columns('*')
            ->where(['random_iv = ?' => bin2hex($iv)]);

        $rememberMe = new static();
        $rs = $rememberMe->getDb()->select($select)->fetch();

        if (! $rs) {
            throw new RuntimeException(sprintf(
                "No database entry found for IV '%s'",
                bin2hex($iv)
            ));
        }

        $rememberMe->aesCrypt = (new AesCrypt())
            ->setKey(hex2bin($rs->passphrase))
            ->setIV($iv);

        if (version_compare(PHP_VERSION, AesCrypt::GCM_SUPPORT_VERSION, '>=')) {
            $tag = base64_decode(array_pop($data));

            if (empty($data)) {
                throw new UnexpectedValueException(sprintf(
                    "Cookie data was encrypted with older method. Php version '%s' uses a new method.",
                    PHP_VERSION
                ));
            }

            $rememberMe->aesCrypt->setTag($tag);
        }

        $rememberMe->username = $rs->username;
        $rememberMe->encryptedPassword = $data[0];

        return $rememberMe;
    }

    /**
     * Create the remember me component from the given username and password
     *
     * @param string $username
     * @param string $password
     *
     * @return static
     */
    public static function fromCredentials($username, $password)
    {
        $aesCrypt = new AesCrypt();
        $rememberMe = new static();
        $rememberMe->encryptedPassword = $aesCrypt->encryptToBase64($password);
        $rememberMe->username = $username;
        $rememberMe->aesCrypt = $aesCrypt;

        return $rememberMe;
    }

    /**
     * Remove expired remember me information from the database
     */
    public static function removeExpired()
    {
        $rememberMe = new static();
        if (! $rememberMe->hasDb()) {
            return;
        }

        $rememberMe->getDb()->delete(static::TABLE, [
            'expires_at < NOW()'
        ]);
    }

    /**
     * Get the remember me cookie
     *
     * @return Cookie
     */
    public function getCookie()
    {
        $values = [
            $this->encryptedPassword,
            base64_encode($this->aesCrypt->getIV()),
        ];

        if (version_compare(PHP_VERSION, AesCrypt::GCM_SUPPORT_VERSION, '>=')) {
            $values = [
                $this->encryptedPassword,
                base64_encode($this->aesCrypt->getTag()),
                base64_encode($this->aesCrypt->getIV()),
            ];
        }

        return (new Cookie(static::COOKIE))
            ->setExpire($this->getExpiresAt())
            ->setHttpOnly(true)
            ->setValue(implode('|', $values));
    }

    /**
     * Get the timestamp when the cookie expires
     *
     * Defaults to now plus 30 days, if not set via {@link setExpiresAt()}.
     *
     * @return int
     */
    public function getExpiresAt()
    {
        if ($this->expiresAt === null) {
            $this->expiresAt = time() + 60 * 60 * 24 * 30;
        }

        return $this->expiresAt;
    }

    /**
     * Set the timestamp when the cookie expires
     *
     * @param int $expiresAt
     *
     * @return $this
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Authenticate via the remember me cookie
     *
     * @return bool
     *
     * @throws \Icinga\Exception\AuthenticationException
     */
    public function authenticate()
    {
        $password = $this->aesCrypt->decryptFromBase64($this->encryptedPassword);
        $auth = Auth::getInstance();
        $authChain = $auth->getAuthChain();
        $authChain->setSkipExternalBackends(true);
        $user = new User($this->username);
        if (! $user->hasDomain()) {
            $user->setDomain(Config::app()->get('authentication', 'default_domain'));
        }

        $authenticated = $authChain->authenticate($user, $password);
        if ($authenticated) {
            $auth->setAuthenticated($user);
        }

        return $authenticated;
    }

    /**
     * Persist the remember me information into the database
     *
     * Any previous stored information is automatically removed.
     *
     * @param string|null $iv
     *
     * @return $this
     */
    public function persist($iv = null)
    {
        if ($iv) {
            $this->remove($iv);
        }

        $this->getDb()->insert(static::TABLE, [
            'username'          => $this->username,
            'passphrase'        => bin2hex($this->aesCrypt->getKey()),
            'random_iv'         => bin2hex($this->aesCrypt->getIV()),
            'http_user_agent'   => (new UserAgent)->getAgent(),
            'expires_at'        => date('Y-m-d H:i:s', $this->getExpiresAt()),
            'ctime'             => new Expression('NOW()'),
            'mtime'             => new Expression('NOW()')
        ]);

        return $this;
    }

    /**
     * Remove remember me information from the database
     *
     * @param string $iv
     *
     * @return $this
     */
    public function remove($iv)
    {
        $this->getDb()->delete(static::TABLE, [
            'random_iv = ?' => bin2hex($iv)
        ]);

        return $this;
    }

    /**
     * Create renewed remember me cookie
     *
     * @return static New remember me cookie which has to be sent to the client
     */
    public function renew()
    {
        return static::fromCredentials(
            $this->username,
            $this->aesCrypt->decryptFromBase64($this->encryptedPassword)
        );
    }

    /**
     * Remove specific remember me information from the database
     *
     * @param string $username
     *
     * @param $iv
     *
     * @return $this
     */
    public function removeSpecific($iv)
    {
        $this->getDb()->delete(static::TABLE, [
            'random_iv = ?' => $iv
        ]);

        return $this;
    }

    /**
     * Get all users using rememberme cookie
     *
     * @return array
     */
    public static function getAllUser()
    {
        $rememberMe = new static();
        if (! $rememberMe->hasDb()) {
            return [];
        }

        $select = (new Select())
            ->from(static::TABLE)
            ->columns('username')
            ->groupBy('username');

        return $rememberMe->getDb()->select($select)->fetchAll();
    }

    /**
     * Get all rememberme cookies of the given user
     *
     * @param $username
     *
     * @return array
     */
    public static function getAllByUsername($username)
    {
        $rememberMe = new static();
        if (! $rememberMe->hasDb()) {
            return [];
        }

        $select = (new Select())
            ->from(static::TABLE)
            ->columns(['http_user_agent', 'random_iv'])
            ->where(['username = ?' => $username]);

        return $rememberMe->getDb()->select($select)->fetchAll();
    }

    /**
     * Get the encrypton/decryption instance
     *
     * @return AesCrypt
     */
    public function getAesCrypt()
    {
        return $this->aesCrypt;
    }
}
