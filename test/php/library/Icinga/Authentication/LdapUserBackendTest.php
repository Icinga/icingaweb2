<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Authentication;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../library/Icinga/Test/BaseTestCase.php');
// @codingStandardsIgnoreEnd

use Icinga\Authentication\Credential;
use \Icinga\Test\BaseTestCase;

// @codingStandardsIgnoreStart
require_once 'Zend/Config.php';
require_once BaseTestCase::$libDir . '/Protocol/Ldap/Connection.php';
require_once BaseTestCase::$libDir . '/Protocol/Ldap/Query.php';
require_once BaseTestCase::$libDir . '/Authentication/Credential.php';
require_once BaseTestCase::$libDir . '/Authentication/UserBackend.php';
require_once BaseTestCase::$libDir . '/Authentication/Backend/LdapUserBackend.php';
// @codingStandardsIgnoreEnd

use \Exception;
use \Zend_Config;
use Icinga\Authentication\Backend\LdapUserBackend;
use Icinga\Protocol\Ldap\Connection;

/**
*
* Test class for Ldapuserbackend
* Created Mon, 10 Jun 2013 07:54:34 +0000
*
**/
class LdapUserBackendTest extends BaseTestCase
{
    // Change this according to your ldap test server
    const ADMIN_DN = 'cn=admin,dc=icinga,dc=org';
    const ADMIN_PASS = 'admin';

    private $users = array(
        'cn=Richard Miles,ou=icinga-unittest,dc=icinga,dc=org' =>  array(
            'cn'            => 'Richard Miles',
            'sn'            => 'Miles',
            'objectclass'   => 'inetOrgPerson',
            'givenName'     => 'Richard',
            'mail'          => 'richard@doe.local',
            'uid'           => 'rmiles',
            'userPassword'  => 'passrmiles'
        ),
        'cn=Jane Woe,ou=icinga-unittest,dc=icinga,dc=org' => array(
            'cn'            => 'Jane Woe',
            'sn'            => 'Woe',
            'objectclass'   => 'inetOrgPerson',
            'givenName'     => 'Jane',
            'mail'          => 'jane@woe.local',
            'uid'           => 'jwoe',
            'userPassword'  => 'passjwoe'
        )
    );

    private $baseOu = array(
        'ou=icinga-unittest,dc=icinga,dc=org' => array(
            'objectclass'   => 'organizationalUnit',
            'ou'            => 'icinga-unittest'
        )
    );

    private function getLDAPConnection()
    {
        $ldapConn = ldap_connect('localhost', 389);

        if (!$ldapConn) {
            $this->markTestSkipped('Could not connect to test-ldap server, skipping test');
        }
        $bind = @ldap_bind($ldapConn, self::ADMIN_DN, self::ADMIN_PASS);

        if (!$bind) {
            $this->markTestSkipped('Could not bind to test-ldap server, skipping test');
        }

        return $ldapConn;
    }

    private function clearTestData($connection)
    {
        foreach ($this->users as $ou => $info) {
            @ldap_delete($connection, $ou);
        }

        foreach ($this->baseOu as $ou => $info) {
            @ldap_delete($connection, $ou);
        }
    }

    private function insertTestdata($connection)
    {
        foreach ($this->baseOu as $ou => $info) {
            if (ldap_add($connection, $ou, $info) === false) {
                $this->markTestSkipped('Couldn\'t set up test-ldap users, skipping test');
            }
        }

        foreach ($this->users as $ou => $info) {
            if (ldap_add($connection, $ou, $info) === false) {
                $this->markTestSkipped('Couldn\'t set up test-ldap users, skipping test');
            }
        }
    }

    protected function setUp()
    {
        $conn = $this->getLDAPConnection();
        $this->clearTestData($conn);
        $this->insertTestData($conn);

        $result = ldap_list($conn, 'ou=icinga-unittest, dc=icinga, dc=org', '(cn=Richard Miles)');

        if (ldap_count_entries($conn, $result) < 1) {
            $this->markTestSkipped('Couldn\'t set up test users, skipping test');
        }

        $result = ldap_list($conn, 'ou=icinga-unittest, dc=icinga, dc=org', '(cn=Jane Woe)');

        if (ldap_count_entries($conn, $result) < 1) {
            $this->markTestSkipped('Couldn\'t set up test users, skipping test');
        }

        ldap_close($conn);
    }

    public function tearDown()
    {
        $conn = $this->getLDAPConnection();

        // $this->clearTestData($conn);
        ldap_close($conn);
    }

    private function createBackendConfig()
    {
        $config = new Zend_Config(
            array(
                'backend'               => 'ldap',
                'target'                => 'user',
                'hostname'              => 'localhost',
                'root_dn'               => 'ou=icinga-unittest,dc=icinga,dc=org',
                'bind_dn'               => 'cn=admin,cn=config',
                'bind_pw'               => 'admin',
                'user_class'            => 'inetOrgPerson',
                'user_name_attribute'   => 'uid'
            )
        );

        return $config;
    }

    /**
     * Test for LdapUserBackend::HasUsername()
     **/
    public function testHasUsername()
    {
        $config = $this->createBackendConfig();
        $backend = new LdapUserBackend(new Connection($config), $config);
        $this->assertTrue($backend->hasUsername(new Credential('jwoe')));
        $this->assertTrue($backend->hasUsername(new Credential('rmiles')));
        $this->assertFalse($backend->hasUsername(new Credential('DoesNotExist')));
    }

    /**
     * Test for LdapUserBackend::Authenticate()
     */
    public function testAuthenticate()
    {
        $config = $this->createBackendConfig();
        $backend = new LdapUserBackend(new Connection($config), $config);

        $this->assertInstanceOf(
            '\Icinga\User',
            $backend->authenticate(new Credential('jwoe', 'passjwoe'))
        );

        $this->assertFalse($backend->authenticate(new Credential('jwoe', 'passjwoe22')));

        $this->assertInstanceOf(
            '\Icinga\User',
            $backend->authenticate(new Credential('rmiles', 'passrmiles'))
        );

        $this->assertFalse($backend->authenticate(new Credential('rmiles', 'passrmiles33')));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Cannot fetch single DN for
     */
    public function testAuthenticateUnknownUser()
    {
        $config = $this->createBackendConfig();
        $backend = new LdapUserBackend(new Connection($config), $config);
        $this->assertFalse($backend->authenticate(new Credential('unknown123', 'passunknown123')));
    }
}
