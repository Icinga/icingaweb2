<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Authentication;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../library/Icinga/Test/BaseTestCase.php');
// @codingStandardsIgnoreEnd

use Icinga\Test\BaseTestCase;

// @codingStandardsIgnoreStart
require_once 'Zend/Config.php';
require_once BaseTestCase::$libDir . '/Authentication/Credential.php';
require_once BaseTestCase::$libDir . '/Authentication/UserBackend.php';
require_once BaseTestCase::$libDir . '/User.php';
// @codingStandardsIgnoreEnd

use \Zend_Config;
use \Icinga\Authentication\Credential;
use \Icinga\Authentication\UserBackend as UserBackend;
use \Icinga\User;

/**
*   Simple backend mock that takes an config object
*   with the property "credentials", which is an array
*   of Credential this backend authenticates
**/
class BackendMock implements UserBackend
{
    public $allowedCredentials = array();
    public $name;

    public function __construct(Zend_Config $config = null)
    {
        if ($config === null) {
            return;
        }

        if (isset ($config->credentials)) {
            $this->allowedCredentials = $config->credentials;
        }

        if ($config->name) {
            $this->name = $config->name;
        } else {
            $this->name = 'TestBackendMock-' . uniqid();
        }
    }

    public function hasUsername(Credential $userCredentials)
    {
        foreach ($this->allowedCredentials as $credential) {
            if ($credential->getUsername() == $userCredentials->getUsername()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Name of the backend
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    public static function getDummyUser()
    {
        return new User(
            'Username',
            'Firstname',
            'Lastname',
            'user@test.local'
        );
    }

    public function getUserCount() {
         return count($this->allowedCredentials);
    }

    public function authenticate(Credential $credentials)
    {
        if (!in_array($credentials, $this->allowedCredentials)) {
            return false;
        }

        return self::getDummyUser();
    }

    public function setCredentials(array $credentials)
    {
        $this->allowedCredentials = $credentials;
    }
}
