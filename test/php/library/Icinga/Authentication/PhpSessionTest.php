<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Authentication;

require_once("../../library/Icinga/Authentication/Session.php");
require_once("../../library/Icinga/Authentication/PhpSession.php");
require_once("../../library/Icinga/Application/Logger.php");
require_once("Zend/Log.php");

use Icinga\Authentication\PhpSession as PhpSession;

class PHPSessionTest extends \PHPUnit_Framework_TestCase
{
    /**
    *   @runInSeparateProcess 
    **/
    public function testSessionCreation()
    {
        $session = new PhpSession();
        $session = new PhpSession(array("ssesion.use_cookies"=>false));
    }

    /**
    *   @runInSeparateProcess 
    **/
    public function testOpenSession()
    {
        $this->assertEquals(session_id(), '');
        $session = new PhpSession();
        $session->open();
        $this->assertNotEquals(session_id(), '');
    }


    /**
    *   @runInSeparateProcess 
    **/
    public function testCloseSession()
    {
        
        $this->assertEquals(session_id(), '');
        $session = new PhpSession();
        $session->open();
        $this->assertNotEquals(session_id(), '');
        $session->close();
    }

    /**
    *   @runInSeparateProcess 
    **/
    public function testPurgeSession()
    {
        $this->assertEquals(session_id(), '');
        $session = new PhpSession();
        $session->open();
        $this->assertNotEquals(session_id(), '');
        $session->purge();
        $this->assertEquals(session_id(), '');
    }
}
