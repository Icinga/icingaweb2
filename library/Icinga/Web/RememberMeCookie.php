<?php
/* Icinga Web 2 | (c) 2020 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Crypt\RSA;
use Icinga\Rememberme\Common\Database;
use ipl\Sql\Select;

/**
 * Cookie for the "remember me" feature upon login with a default expiration time of 30 days
 *
 */
/*class RememberMeCookie extends Cookie
{
    public function __construct()
    {
        parent::__construct('remember-me');

        $this->setExpire(time() + 60 * 60 * 24 * 30);
        $this->setHttpOnly(true);
    }

   // public static function create
}*/
class RememberMe
{
    use Database;

    const COOKIE = 'remember-me';

    protected $encryptedPassword;

    protected $rsa;

    protected $username;

    public static function fromCookie()
    {
        if (! isset($_COOKIE[static::COOKIE])) {
            throw new UnexpectedValueException('');
        }

        // Grab cookie values
        // Select from database
        // Assign variables
        $data = explode('|', $_COOKIE[static::COOKIE]);
        $publicKey = base64_decode(array_pop($data));

        $select = (new Select())
            ->from('rememberme')
            ->columns('*')
            ->where(['public_key = ?' => $publicKey]);

        $dbData = self::getDb()->select($select)->fetch();
        $newData = [];
        foreach ($dbData as $key => $value) {
            $newData[$key] = $value;
        }


        $rememberMe->encryptedPassword = $data[1];
        $rememberMe->rsa = ...;
        $rememberMe->username = $data[0];
    }

    public static function fromCredentials($username, $password)
    {
        $rememberMe = new static();

        $rsa = (new RSA())->loadKey(...RSA::keygen());

        $rememberMe->encryptedPassword = $rsa->encryptToBase64($password);
        $rememberMe->username =  $rsa->encryptToBase64($username);
        $rememberMe->rsa = $rsa;
    }

    public function getCookie()
    {
        return (new Cookie(static::COOKIE))
            ->setExpire(time() + 60 * 60 * 24 * 30)
            ->setHttpOnly(true)
            ->setValue(...);
    }

    public function authenticate()
    {
        // Auth code here - this is the only place where we decrypt the password
    }

    public function persist()
    {
        // Insert into database
        $this->getDb()->delete('rememberme', [
            'username = ?' => $user->getUsername()
        ]);
    }

    public function remove()
    {
        // Remove from database
        $this->getDb()->insert('rememberme', [
            'username' => $user->getUsername(),
            'private_key' => $rsa->getPrivateKey(),
            'public_key' => $rsa->getPublicKey(),
            'ctime' => date('Y-m-d H:i:s'),
            'mtime' => date('Y-m-d H:i:s')
        ]);
    }
}
