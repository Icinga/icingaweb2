<?php

// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Authentication;

/**
*
* Test class for Ldapuserbackend 
* Created Mon, 10 Jun 2013 07:54:34 +0000 
*
**/
class LdapuserbackendTest extends \PHPUnit_Framework_TestCase
{
    // Change this according to your ldap test server
    const ADMIN_DN = "cn=admin,dc=icinga,dc=org";
    const ADMIN_PASS = "admin";

    private $users = array(
        "cn=John Doe, dc=icinga, dc=org" =>  array(
            "cn" => "John Doe",
            "sn" => "Doe",
            "objectclass" => "inetOrgPerson",
            "givenName" => "John",
            "mail" => "john@doe.local"
        ),
        "cn=Jane Woe, dc=icinga, dc=org" => array(
            "cn" => "Jane Woe",
            "sn" => "Woe",
            "objectclass" => "inetOrgPerson",
            "givenName" => "Jane",
            "mail" => "jane@woe.local"
        )
    );

    private function getLDAPConnection()
    {
        $this->markTestSkipped("LDAP User Backend is currently not testable, as it would require to Boostrap most of the application (see Protocol\Ldap\Connection)");
        return;
        $ldapConn = ldap_connect("localhost", 389);
        if (!$ldapConn) {
            $this->markTestSkipped("Could not connect to test-ldap server, skipping test");
            return null;
        }
        $bind = ldap_bind($ldapConn, self::ADMIN_DN, self::ADMIN_PASS);
        if (!$bind) {
            $this->markTestSkipped("Could not bind to test-ldap server, skipping test");
            return null;
        }
        return $ldapConn;
    }
    
    private function clearTestData($connection)
    {
        foreach ($this->users as $ou => $info) {
            @ldap_delete($connection, $ou);
        }
    }

    private function insertTestdata($connection)
    {
        foreach ($this->users as $ou => $info) {
            if (ldap_add($connection, $ou, $info) === false) {
                $this->markTestSkipped("Couldn't set up test-ldap users, skipping test");
            }
        }

    }

    protected function setUp()
    {
        $conn = $this->getLDAPConnection();
        if ($conn == null) {
            return;
        }
        $this->clearTestData($conn);
        $this->insertTestData($conn);
        $result = ldap_list($conn, "dc=icinga, dc=org", "(cn=John Doe)");
        if (ldap_count_entries($conn, $result) < 1) {
            $this->markTestSkipped("Couldn't set up test users, skipping test");
        }
        $result = ldap_list($conn, "dc=icinga, dc=org", "(cn=Jane Woe)");
        if (ldap_count_entries($conn, $result) < 1) {
            $this->markTestSkipped("Couldn't set up test users, skipping test");
        }
        ldap_close($conn);
    }

    public function tearDown()
    {
        $conn = $this->getLDAPConnection();
        if ($conn == null) {
            return;
        }

        $this->clearTestData($conn);
        ldap_close($conn);
    }

    /**
    * Test for LdapUserBackend::HasUsername() 
    *
    **/
    public function testHasUsername()
    {
    }

    /**
    * Test for LdapUserBackend::Authenticate() 
    *
    **/
    public function testAuthenticate()
    {
        $this->markTestIncomplete('testAuthenticate is not implemented yet');
    }
}
