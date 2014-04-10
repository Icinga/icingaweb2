<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Authentication;

use \Exception;
use \Zend_Config;
use Icinga\Test\BaseTestCase;
use Icinga\Authentication\Backend\LdapUserBackend;
use Icinga\Protocol\Ldap\Connection as LdapConnection;

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

    /**
     * Create a backend config and initialise the LdapConnection to the testing backend manually,
     * to prevent the LdapUserBackend from calling the unitialised ResourceFactory
     *
     * @return Zend_Config  The authentication backend configuration
     */
    private function createBackendConfig()
    {
        $resourceConfig = new Zend_Config(
            array(
                'hostname'              => 'localhost',
                'root_dn'               => 'ou=icinga-unittest,dc=icinga,dc=org',
                'bind_dn'               => 'cn=admin,cn=config',
                'bind_pw'               => 'admin'
            )
        );
        $backendConfig = new Zend_Config(
            array(
                'resource' => new LdapConnection($resourceConfig),
                'target' => 'user',
                'user_class'            => 'inetOrgPerson',
                'user_name_attribute'   => 'uid'
            )
        );
        return $backendConfig;
    }

    /**
     * Test for LdapUserBackend::HasUsername()
     **/
    public function testHasUsername()
    {
        $this->markTestSkipped('Backend creation has been decoupled');
        $backend = new LdapUserBackend($this->createBackendConfig());
        $this->assertTrue($backend->hasUsername(new Credential('jwoe')));
        $this->assertTrue($backend->hasUsername(new Credential('rmiles')));
        $this->assertFalse($backend->hasUsername(new Credential('DoesNotExist')));
    }

    /**
     * Test for LdapUserBackend::Authenticate()
     */
    public function testAuthenticate()
    {
        $this->markTestSkipped('Backend creation has been decoupled');
        $backend = new LdapUserBackend($this->createBackendConfig());

        $this->assertInstanceOf(
            '\Icinga\User',
            $backend->authenticate(new Credential('jwoe', 'passjwoe'))
        );

        $this->assertNull($backend->authenticate(new Credential('jwoe', 'passjwoe22')));

        $this->assertInstanceOf(
            '\Icinga\User',
            $backend->authenticate(new Credential('rmiles', 'passrmiles'))
        );

        $this->assertNull($backend->authenticate(new Credential('rmiles', 'passrmiles33')));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Cannot fetch single DN for
     */
    public function testAuthenticateUnknownUser()
    {
        $this->markTestSkipped('Backend creation has been decoupled');
        $backend = new LdapUserBackend($this->createBackendConfig());
        $this->assertFalse($backend->authenticate(new Credential('unknown123', 'passunknown123')));
    }
}
