<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Protocol\Ldap;

use Icinga\Data\ConfigObject;
use Icinga\Test\BaseTestCase;
use Icinga\Protocol\Ldap\Connection;

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

        $connection = new Connection($config);
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

    public function testLimit()
    {
        $select = $this->prepareSelect();
        $this->assertEquals(10, $select->getLimit());
        $this->assertEquals(4, $select->getOffset());
    }

    public function testHasLimit()
    {
        $select = $this->emptySelect();
        $this->assertFalse($select->hasLimit());
        $select = $this->prepareSelect();
        $this->assertTrue($select->hasLimit());
    }

    public function testHasOffset()
    {
        $select = $this->emptySelect();
        $this->assertFalse($select->hasOffset());
        $select = $this->prepareSelect();
        $this->assertTrue($select->hasOffset());
    }

    public function testGetLimit()
    {
        $select = $this->prepareSelect();
        $this->assertEquals(10, $select->getLimit());
    }

    public function testGetOffset()
    {
        $select = $this->prepareSelect();
        $this->assertEquals(10, $select->getLimit());
    }

    public function testFetchTree()
    {
        $this->markTestIncomplete('testFetchTree is not implemented yet - requires real LDAP');
    }

    public function testFrom()
    {
        return $this->testListFields();
    }

    public function testWhere()
    {
        $this->markTestIncomplete('testWhere is not implemented yet');
    }

    public function testOrder()
    {
        $select = $this->emptySelect()->order('bla');
        // tested by testGetSortColumns
    }

    public function testListFields()
    {
        $select = $this->prepareSelect();
        $this->assertEquals(
            array('testIntColumn', 'testStringColumn'),
            $select->listFields()
        );
    }

    public function testGetSortColumns()
    {
        $select = $this->prepareSelect();
        $cols = $select->getSortColumns();
        $this->assertEquals('testIntColumn', $cols[0][0]);
    }

    public function testCreateQuery()
    {
        $select = $this->prepareSelect();
        $res = '(&(objectClass=dummyClass)(testIntColumn=1)(testStringColumn=test)(testWildcard=abc*))';
        $this->assertEquals($res, $select->create());
    }
}
