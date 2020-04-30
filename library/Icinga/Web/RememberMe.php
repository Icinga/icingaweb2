<?php
/* Icinga Web 2 | (c) 2020 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Config;
use Icinga\Authentication\Auth;
use Icinga\Crypt\RSA;
use Icinga\Common\Database;
use Icinga\User;
use ipl\Sql\Expression;
use ipl\Sql\Select;
use RuntimeException;

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

    /** @var RSA RSA keys for encrypting/decrypting the credentials */
    protected $rsa;

    /** @var int Timestamp when the remember me cookie expires */
    protected $expiresIn;

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
        $publicKey = base64_decode(array_pop($data));

        $select = (new Select())
            ->from(static::TABLE)
            ->columns('*')
            ->where(['public_key = ?' => $publicKey]);

        $rememberMe = new static();
        $rs = $rememberMe->getDb()->select($select)->fetch();

        if (! $rs) {
            throw new RuntimeException(sprintf(
                "No database entry found for public key '%s'",
                $publicKey
            ));
        }

        $rememberMe->rsa = (new RSA())->loadKey($rs->private_key, $publicKey);
        $rememberMe->username = $rememberMe->rsa->decryptFromBase64($data[0]);
        $rememberMe->encryptedPassword = $data[1];

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
        $rememberMe = new static();

        $rsa = (new RSA())->loadKey(...RSA::keygen());

        $rememberMe->encryptedPassword = $rsa->encryptToBase64($password);
        $rememberMe->username = $username;
        $rememberMe->rsa = $rsa;

        return $rememberMe;
    }

    /**
     * Remove expired remember me information from the database
     */
    public static function removeExpired()
    {
        (new static())->getDb()->delete(static::TABLE, [
            'expires_in < NOW()'
        ]);
    }

    /**
     * Get the remember me cookie
     *
     * @return Cookie
     */
    public function getCookie()
    {
        return (new Cookie(static::COOKIE))
            ->setExpire($this->getExpiresIn())
            ->setHttpOnly(true)
            ->setValue(implode('|', [
                $this->rsa->encryptToBase64($this->username),
                $this->encryptedPassword,
                base64_encode($this->rsa->getPublicKey())
            ]));
    }

    /**
     * Get the timestamp when the cookie expires
     *
     * Defaults to now plus 30 days, if not set via {@link setExpiresIn()}.
     *
     * @return int
     */
    public function getExpiresIn()
    {
        if ($this->expiresIn === null) {
            $this->expiresIn = time() + 60 * 60 * 24 * 30;
        }

        return $this->expiresIn;
    }

    /**
     * Set the timestamp when the cookie expires
     *
     * @param int $expiresIn
     *
     * @return $this
     */
    public function setExpiresIn($expiresIn)
    {
        $this->expiresIn = $expiresIn;

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
        $password = $this->rsa->decryptFromBase64($this->encryptedPassword);
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
     * @return $this
     */
    public function persist()
    {
        $this->remove();

        $this->getDb()->insert(static::TABLE, [
            'username'    => $this->username,
            'private_key' => $this->rsa->getPrivateKey(),
            'public_key'  => $this->rsa->getPublicKey(),
            'expires_in'  => date('Y-m-d H:i:s', $this->getExpiresIn()),
            'ctime'       => new Expression('NOW()'),
            'mtime'       => new Expression('NOW()')
        ]);

        return $this;
    }

    /**
     * Remove remember me information from the database
     *
     * @param string $username
     *
     * @return $this
     */
    public function remove($username = null)
    {
        $this->getDb()->delete(static::TABLE, [
            'username = ?' => $username ?: $this->username
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
            $this->rsa->decryptFromBase64($this->encryptedPassword)
        );
    }
}
