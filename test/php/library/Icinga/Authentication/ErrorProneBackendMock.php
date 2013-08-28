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
require_once BaseTestCase::$libDir . '/Authentication/Credentials.php';
require_once BaseTestCase::$libDir . '/Authentication/UserBackend.php';
require_once BaseTestCase::$libDir . '/User.php';
// @codingStandardsIgnoreEnd

use \Exception;
use \Zend_Config;
use \Icinga\Authentication\Credentials as Credentials;
use \Icinga\Authentication\UserBackend as UserBackend;
use \Icinga\User;

/**
 *   Simple backend mock that takes an config object
 *   with the property "credentials", which is an array
 *   of Credentials this backend authenticates
 **/
class ErrorProneBackendMock implements UserBackend
{
    public static $throwOnCreate = false;

    public $name;

    /**
     * Creates a new object
     *
     * @param   Zend_Config $config
     * @throws  Exception
     */
    public function __construct(Zend_Config $config)
    {
        if (self::$throwOnCreate === true) {
            throw new Exception('__construct error: Could not create');
        }

        if ($config->name) {
            $this->name = $config->name;
        } else {
            $this->name = 'TestBackendErrorProneMock-' . uniqid();
        }
    }

    /**
     * Test if the username exists
     *
     * @param   Credentials $credentials
     *
     * @return  bool
     * @throws  Exception
     */
    public function hasUsername(Credentials $credentials)
    {
        throw new Exception('hasUsername error: ' . $credentials->getUsername());
    }

    /**
     * Authenticate
     *
     * @param   Credentials $credentials
     *
     * @return  User
     * @throws  Exception
     */
    public function authenticate(Credentials $credentials)
    {
        throw new Exception('authenticate error: ' . $credentials->getUsername());
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
}
