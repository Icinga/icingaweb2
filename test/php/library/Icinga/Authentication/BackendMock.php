<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Authentication;

require_once __DIR__. '/../../../../../library/Icinga/Authentication/Credentials.php';
require_once __DIR__. '/../../../../../library/Icinga/Authentication/UserBackend.php';
require_once __DIR__. '/../../../../../library/Icinga/User.php';

use Icinga\Authentication\Credentials as Credentials;
use Icinga\Authentication\UserBackend as UserBackend;
use Icinga\User;

/**
*   Simple backend mock that takes an config object  
*   with the property "credentials", which is an array 
*   of Credentials this backend authenticates
**/
class BackendMock implements UserBackend
{
    public $allowedCredentials = array();
    public function __construct($config = null)
    {
        if ($config === null) {
            return;
        }
        if (isset ($config->credentials)) {
            $this->allowedCredentials = $config->credentials;
        }
    }

    public function hasUsername(Credentials $userCredentials)
    {
        foreach ($this->allowedCredentials as $credential) {
            if ($credential->getUsername() == $userCredentials->getUsername()) {
                return true;
            }
        }
        return false;
    }
    
    public static function getDummyUser()
    {
        return new User(
            "Username",
            "Firstname",
            "Lastname",
            "user@test.local"
        );
    }

    public function authenticate(Credentials $credentials)
    {
        if (!in_array($credentials, $this->allowedCredentials)) {
            return false;
        }
        return self::getDummyUser();
    }
}
