<?php
namespace Tests\Icinga\Protocol\Ldap;
require_once '../library/Icinga/Protocol/Ldap/Query.php';
require_once '../library/Icinga/Protocol/Ldap/Connection.php';
require_once '../library/Icinga/Protocol/Ldap/LdapUtils.php';
/**
*
* Test class for Query
* Created Wed, 13 Mar 2013 12:57:11 +0000 
*
**/
class QueryTest extends \PHPUnit_Framework_TestCase
{
    private function emptySelect()
    {
        $connection = new \Icinga\Protocol\Ldap\Connection((object) array(
            'hostname' => 'localhost',
            'root_dn'  => 'dc=example,dc=com',
            'bind_dn'  => 'cn=user,ou=users,dc=example,dc=com',
            'bind_pw'  => '***'
        ));
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

    /**
    * Test for Query::Count() - shall be tested with connection
    *
    **/
    public function testCount()
    {
    }

    /**
    * Test for Query::Limit() 
    *
    **/
    public function testLimit()
    {
        $select = $this->prepareSelect();
        $this->assertEquals(10, $select->getLimit());
        $this->assertEquals(4, $select->getOffset());
    }

    /**
    * Test for Query::HasLimit() 
    *
    **/
    public function testHasLimit()
    {
        $select = $this->emptySelect();
        $this->assertFalse($select->hasLimit());
        $select = $this->prepareSelect();
        $this->assertTrue($select->hasLimit());
    }

    /**
    * Test for Query::HasOffset() 
    *
    **/
    public function testHasOffset()
    {
        $select = $this->emptySelect();
        $this->assertFalse($select->hasOffset());
        $select = $this->prepareSelect();
        $this->assertTrue($select->hasOffset());
    }

    /**
    * Test for Query::GetLimit() 
    *
    **/
    public function testGetLimit()
    {
        $select = $this->prepareSelect();
        $this->assertEquals(10, $select->getLimit());
    }

    /**
    * Test for Query::GetOffset() 
    *
    **/
    public function testGetOffset()
    {
        $select = $this->prepareSelect();
        $this->assertEquals(10, $select->getLimit());
    }

    /**
    * Test for Query::FetchTree() 
    *
    **/
    public function testFetchTree()
    {
        $this->markTestIncomplete('testFetchTree is not implemented yet - requires real LDAP');
    }

    /**
    * Test for Query::FetchAll() - shall be tested with connection
    *
    **/
    public function testFetchAll()
    {
    }

    /**
    * Test for Query::FetchRow() - shall be tested with connection
    *
    **/
    public function testFetchRow()
    {
    }

    /**
    * Test for Query::FetchOne() 
    *
    **/
    public function testFetchOne()
    {
    }

    /**
    * Test for Query::FetchPairs() 
    *
    **/
    public function testFetchPairs()
    {
    }

    /**
    * Test for Query::From() 
    *
    **/
    public function testFrom()
    {
        return $this->testListFields();
    }

    /**
    * Test for Query::Where() 
    *
    **/
    public function testWhere()
    {
        $this->markTestIncomplete('testWhere is not implemented yet');
    }

    /**
    * Test for Query::Order() 
    *
    **/
    public function testOrder()
    {
        $select = $this->emptySelect()->order('bla');
        // tested by testGetSortColumns
    }

    /**
    * Test for Query::ListFields() 
    *
    **/
    public function testListFields()
    {
        $select = $this->prepareSelect();
        $this->assertEquals(
            array('testIntColumn', 'testStringColumn'),
            $select->listFields()
        );
    }

    /**
    * Test for Query::GetSortColumns() 
    *
    **/
    public function testGetSortColumns()
    {
        $select = $this->prepareSelect();
        $cols = $select->getSortColumns();
        $this->assertEquals('testIntColumn', $cols[0][0]);
    }

    /**
    * Test for Query::Paginate() - requires real result
    *
    **/
    public function testPaginate()
    {
    }

    /**
    * Test for Query::__toString() 
    *
    **/
    public function test__toString()
    {
        $select = $this->prepareSelect();
        $res = '(&(objectClass=dummyClass)(testIntColumn=1)(testStringColumn=test)(testWildcard=abc*))';
        $this->assertEquals($res, (string) $select);
    }

    /**
    * Test for Query::__destruct() 
    *
    **/
    public function test__destruct()
    {
    }

}
