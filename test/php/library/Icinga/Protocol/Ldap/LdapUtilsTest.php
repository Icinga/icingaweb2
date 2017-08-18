<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Protocol\Ldap;

use Icinga\Protocol\Ldap\LdapUtils;
use Icinga\Test\BaseTestCase;

class LdapUtilsTest extends BaseTestCase
{
    protected static $validDn = array(
        'dc=example,dc=com',
        'dc=example, dc=com',
        'dc = example , dc = com',
        'DC=EXAMPLE,DC=COM',
        '0.9.2342.19200300.100.1.25=Example,0.9.2342.19200300.100.1.25=Com',
        'CN=host,OU=Datacenter Servers,DC=example,DC=com',
        'CN=Doe\, John,OU=Admin Users,DC=example,DC=com'
    );

    protected static $invalidDn = array(
        'testuser',
        'heinzimüller',
        'test.user@example.com',
        'test,user@example.com',
    );

    public function testIsDnForValidValues()
    {
        foreach (static::$validDn as $dn) {
            $this->assertTrue(LdapUtils::isDn($dn), 'DN should be tested as valid value: ' . $dn);
        }
    }

    public function testIsDnForInvalidValues()
    {
        foreach (static::$invalidDn as $dn) {
            $this->assertFalse(LdapUtils::isDn($dn), 'DN should be tested as invalid value: ' . $dn);
        }
    }
}
