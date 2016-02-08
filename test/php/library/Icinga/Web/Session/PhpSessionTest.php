<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Web\Session;

use Icinga\Test\BaseTestCase;
use Icinga\Web\Session\PhpSession;

class PhpSessionTest extends BaseTestCase
{
    private function getSession()
    {
        if (!is_writable('/tmp')) {
            $this->markTestSkipped('Could not write to session directory');
        }
        return new PhpSession(
            array(
                'use_cookies'   => false,
                'save_path'     => '/tmp',
                'test_session_name' => 'IcingawebUnittest'
            )
        );
    }
    /**
     * Test the creation of a PhpSession object
     *
     * @runInSeparateProcess
     */
    public function testSessionCreation()
    {
        $this->getSession();
    }

    /**
     * Test PhpSession::open()
     *
     * @runInSeparateProcess
     */
    public function testSessionReadWrite()
    {
        $session = $this->getSession();
        $session->purge();
        $this->assertEquals(null, $session->get('key'));
        $session->set('key', 'value');
        $session->write();
        $session->read();
        $this->assertEquals('value', $session->get('key'));
    }

    /**
     * Test a session being closed by PhpSession::close()
     *
     * @runInSeparateProcess
     */
    public function testPurgeSession()
    {
        $session = $this->getSession();
        $session->set('key2', 'value2');
        $session->purge();
        $session->read();
        $this->assertEquals(null, $session->get('key2'));
    }

    /**
     * Test whether session namespaces are properly written, cleared and loaded
     *
     * @runInSeparateProcess
     */
    public function testNamespaceReadWrite()
    {
        $session = $this->getSession();
        $namespace = $session->getNamespace('test');
        $namespace->set('some_key', 'some_val');
        $namespace->set('an_array', array(1, 2, 3));
        $session->write();
        $session->clear();
        $this->assertFalse($session->hasNamespace('test'));
        $session->read();
        $namespace = $session->getNamespace('test');
        $this->assertEquals($namespace->get('some_key'), 'some_val');
        $this->assertEquals($namespace->get('an_array'), array(1, 2, 3));
    }

    /**
     * Test whether session values are properly removed
     *
     * @runInSeparateProcess
     */
    public function testValueRemoval()
    {
        $session = $this->getSession();
        $session->set('key', 'value');
        $session->write();
        $session->delete('key');
        $session->write();
        $session->clear();
        $session->read();
        $this->assertNull($session->get('key'));
    }

    /**
     * Test whether session namespaces are properly removed
     *
     * @runInSeparateProcess
     */
    public function testNamespaceRemoval()
    {
        $session = $this->getSession();
        $namespace = $session->getNamespace('test');
        $namespace->key = 'value';
        $session->write();
        $session->removeNamespace('test');
        $session->write();
        $session->clear();
        $session->read();
        $this->assertFalse($session->hasNamespace('test'));
    }
}
