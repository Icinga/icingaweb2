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

class RememberMe
{
    use Database;

    /**
     * Constant cookie
     */
    const COOKIE = 'remember-me';

    /**
     * @var string
     */
    protected $encryptedPassword;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var object
     */
    protected $rsa;

    protected $expiresIn;

    /**
     * Check if cookie is set
     *
     * @return bool
     */
    public static function hasCookie()
    {
        return isset($_COOKIE[static::COOKIE]);
    }

    /**
     * Get cookie values
     *
     * @return static
     */
    public static function fromCookie()
    {
        $data = explode('|', $_COOKIE[static::COOKIE]);
        $publicKey = base64_decode(array_pop($data));

        $select = (new Select())
            ->from('rememberme')
            ->columns('*')
            ->where(['public_key = ?' => $publicKey]);

        $rememberMe = new static();
        $rs = $rememberMe->getDb()->select($select)->fetch();
        if (! $rs) {
            throw new RuntimeException('No database entry found for the given key');
        }
        $rememberMe->rsa = (new RSA())->loadKey($rs->private_key, $publicKey);
        $rememberMe->username = $rememberMe->rsa->decryptFromBase64($data[0]);
        $rememberMe->encryptedPassword = $data[1];

        return $rememberMe;
    }

    /**
     * Encrypt the given password and assign the variables
     *
     * @param $username
     * @param $password
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
     * Set the values for 'remember-me' cookie
     *
     * @return Cookie
     */
    public function getCookie()
    {
        $value = [
            $this->rsa->encryptToBase64($this->username),
            $this->encryptedPassword,
            base64_encode($this->rsa->getPublicKey())
        ];

        return (new Cookie(static::COOKIE))
            ->setExpire(time() + 60 * 60 * 24 * 30)
            ->setHttpOnly(true)
            ->setValue(implode('|', $value));
    }

    /**
     * Unset the values for 'remember-me' cookie
     *
     * @return Cookie
     */
    public static function forget()
    {
        unset($_COOKIE[static::COOKIE]);

        return (new Cookie(static::COOKIE))
            ->setHttpOnly(true)
            ->forgetMe();
    }

    /**
     * Authenticate the given username and password
     *
     * @return bool     True if authentication succeed, false if not
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
     * Database insert for the given private and public key
     *
     * Remove old entry for given user if exists
     * Save new keys in database
     */
    public function persist()
    {
        $this->remove();

        $this->getDb()->insert('rememberme', [
            'username' => $this->username,
            'private_key' => $this->rsa->getPrivateKey(),
            'public_key' => $this->rsa->getPublicKey(),
            'expires_in' => new Expression('FROM_UNIXTIME(?)', $this->getExpiresIn()),
            'ctime' => new Expression('NOW()'),
            'mtime' => new Expression('NOW()')
        ]);
    }

    /**
     * Delete database entry if user logout or new keys are created for the same user
     *
     * by logout, this class do not have the login data for user, so username must
     * be given as parameter to delete the user information from database.
     *
     * @param null|$username
     */
    public function remove($username = null)
    {
        $this->getDb()->delete('rememberme', [
            'username = ?' => $this->username ?: $username
        ]);
    }

    /**
     * Renew the cookie
     *
     * @return $this
     */
    public function renew()
    {
        return static::fromCredentials(
            $this->username,
            $this->rsa->decryptFromBase64($this->encryptedPassword)
        );
    }

    /**
     * Get expiry date
     *
     * set if not already set
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
     *  Remove expired database entry
     */
    public static function removeExpired()
    {
         (new static())->getDb()->delete('rememberme', [
            'expires_in < ?' => new Expression('NOW()')
         ]);
    }
}
