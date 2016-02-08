<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Protocol\Ldap;

use Icinga\Data\ConfigObject;
use Icinga\Test\BaseTestCase;
use Icinga\Protocol\Ldap\LdapConnection;

class QueryTest extends BaseTestCase
{
    private function emptySelect()
    {
        $config = new ConfigObject(
            array(
                'hostname' => 'localhost',
                'root_dn'  => 'dc=example,dc=com',
                'bind_dn'  => 'cn=user,ou=users,dc=example,dc=com',
                'bind_pw'  => '***'
            )
        );

        $connection = new LdapConnection($config);
        return $connection->select();
    }

    private function prepareSelect()
    {
        $select = $this->emptySelect();
        $select->from('dummyClass', array('testIntColumn', 'testStringColumn'))
            ->where('testIntColumn', 1)
            ->where('testStringColumn', 'test')
            ->where('testWildcard', 'abc*')
            ->order('testIntColumn')
            ->limit(10, 4);
        return $select;
    }

    public function testFetchTree()
    {
        $this->markTestIncomplete('testFetchTree is not implemented yet - requires real LDAP');
    }

    public function testWhere()
    {
        $this->markTestIncomplete('testWhere is not implemented yet');
    }

    public function testOrder()
    {
        $this->markTestIncomplete('testOrder is not implemented yet, order support for ldap queries is incomplete');
    }

    public function testRenderFilter()
    {
        $select = $this->prepareSelect();
        $res = '(&(objectClass=dummyClass)(testIntColumn=1)(testStringColumn=test)(testWildcard=abc*))';
        $this->assertEquals($res, (string) $select);
    }
}
