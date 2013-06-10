<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Authentication;

require_once("../../library/Icinga/Application/Logger.php");
require_once("../../library/Icinga/Authentication/Manager.php");
require_once("../../library/Icinga/Authentication/Credentials.php");
require_once("Zend/Log.php");
require_once("BackendMock.php");
require_once("SessionMock.php");

use Icinga\Authentication\Manager as AuthManager;
use Icinga\Authentication\Credentials as Credentials;

/**
*
* Test class for Manager 
* Created Mon, 10 Jun 2013 07:54:34 +0000 
*
**/
class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function getTestCredentials()
    {
        return (object) array("credentials" => array(
            new Credentials("jdoe", "passjdoe"),
            new Credentials("root", "passroot"),
            new Credentials("test", "passtest")
        ));
    }

    public function getManagerInstance(&$session = null, $write = false)
    {
        if ($session == null) {
            $session = new SessionMock();
        }
        return  AuthManager::getInstance(
            (object) array(),
            array(
                "userBackendClass" => new BackendMock(
                    $this->getTestCredentials()
                ),
                "groupBackendClass" => new BackendMock(),
                "sessionClass" => $session,
                "writeSession" => $write
            )
        );
    }

    public function testManagerInstanciation()
    {
        AuthManager::clearInstance();
        $this->setPreserveGlobalState(false);
        $authMgr = $this->getManagerInstance();
        $auth = $this->assertEquals($authMgr, AuthManager::getInstance());
        AuthManager::clearInstance();
    }


    public function testAuthentication()
    {
        AuthManager::clearInstance();
        $auth = $this->getManagerInstance();
        $this->assertFalse(
            $auth->authenticate(
                new Credentials("jhoe", "passjdoe"),
                false
            )
        );
        $this->assertFalse(
            $auth->authenticate(
                new Credentials("joe", "passjhoe"),
                false
            )
        );
        $this->assertTrue(
            $auth->authenticate(
                new Credentials("jdoe", "passjdoe"),
                false
            )
        );
        AuthManager::clearInstance();
    }

    public function testPersistAuthInSession()
    {
        AuthManager::clearInstance();
        $session = new SessionMock();
        $auth = $this->getManagerInstance($session, true);
        $this->assertFalse($auth->isAuthenticated(true));
        $auth->authenticate(new Credentials("jdoe", "passjdoe"));
        $this->assertNotEquals(null, $session->get("user"));
        $user = $session->get("user");
        $this->assertEquals("Username", $user->getUsername());
        $this->assertTrue($auth->isAuthenticated(true));
        AuthManager::clearInstance();
    }

    public function testAuthenticateFromSession()
    {
        AuthManager::clearInstance();
        $session = new SessionMock();
        $session->set("user", BackendMock::getDummyUser());
        $auth = $this->getManagerInstance($session, false);
        $this->assertFalse($auth->isAuthenticated(true));
        $this->assertTrue($auth->isAuthenticated());
        $this->assertTrue($auth->isAuthenticated());
    }

    /**
    *
    *   @expectedException \Exception
    **/
    public function testWriteSessionTwice()
    {
        $auth = $this->getManagerInstance($session, false);
        $this->assertFalse($auth->isAuthenticated(true));
        $auth->authenticate(new Credentials("jdoe", "passjdoe"));
        
    }
}
