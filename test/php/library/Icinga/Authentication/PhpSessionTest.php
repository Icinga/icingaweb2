<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Authentication;

require_once('../../library/Icinga/Authentication/Session.php');
require_once('../../library/Icinga/Authentication/PhpSession.php');
require_once('../../library/Icinga/Application/Logger.php');
require_once('../../library/Icinga/Exception/ConfigurationError.php');
require_once('Zend/Log.php');

use Icinga\Authentication\PhpSession as PhpSession;

class PHPSessionTest extends \PHPUnit_Framework_TestCase
{

    private function getSession()
    {

        if (!is_writable('/tmp')) {
            $this->markTestSkipped('Could not write to session directory');
            return;
        }
        return new PhpSession(array(
            'use_cookies'   => false,
            'save_path'     => '/tmp'
        ));

    }
    /**
    *   Test the creation of a PhpSession object
    *
    *   @runInSeparateProcess
    **/
    public function testSessionCreation()
    {
        $this->getSession();
    }

    /**
     *   Test PhpSession::open()
     *
     *   @runInSeparateProcess
     */
    public function testOpenSession()
    {
        $this->assertEquals(session_id(), '', 'Asserting test precondition: session not being setup yet ');
        $session = $this->getSession();
        $session->open();
        $this->assertNotEquals(session_id(), '', 'Asserting a Session ID being available after PhpSession::open()');
    }


    /**
     *  Test a session being closed by PhpSession::close()
     *
     *  @runInSeparateProcess
     **/
    public function testCloseSession()
    {
        $this->assertEquals(session_id(), '', 'Asserting test precondition: session not being setup yet ');
        $session = $this->getSession();
        $session->open();
        $this->assertNotEquals(session_id(), '',  'Asserting a Session ID being available after PhpSession::open()');
        $session->close();
    }

    /**
     *  Test if a session is correctly purged when calling PhpSession::purge()
     *
     *  @runInSeparateProcess
     */
    public function testPurgeSession()
    {
        $this->assertEquals(session_id(), '', 'Asserting test precondition: session not being setup yet ');
        $session = $this->getSession();
        $session->open();
        $this->assertNotEquals(session_id(), '',  'Asserting a Session ID being available after PhpSession::open()');
        $session->purge();
        $this->assertEquals(session_id(), '',  'Asserting no Session ID being available after PhpSession::purge()');
    }
}
