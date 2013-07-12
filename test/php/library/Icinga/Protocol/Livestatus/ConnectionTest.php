<?php

namespace Tests\Icinga\Protocol\Livestatus;

use Icinga\Protocol\Livestatus\Connection;
use Icinga\Protocol\Livestatus\Query;
use PHPUnit_Framework_TestCase as TestCase;

require_once('../../library/Icinga/Protocol/AbstractQuery.php');
require_once('../../library/Icinga/Protocol/Livestatus/Connection.php');
require_once('../../library/Icinga/Protocol/Livestatus/Query.php');

/**
*
* Test class for Connection 
*
**/
class ConnectionTest extends TestCase
{

    /**
    * Test for Connection::HasTable() 
    *
    **/
    public function testHasTable()
    {
        $this->markTestIncomplete('testHasTable is not implemented yet');
    }

    /**
    * Test for Connection::Select() 
    *
    **/
    public function testSelect()
    {
        $socket = tempnam(sys_get_temp_dir(), 'IcingaTest');
        $connection = new Connection($socket);
        $this->assertTrue($connection->select() instanceof Query);
        unlink($socket);
    }

    /**
    * Test for Connection::FetchAll() 
    *
    **/
    public function testFetchAll()
    {
        $this->markTestIncomplete('testFetchAll is not implemented yet');
    }

    /**
    * Test for Connection::Disconnect() 
    *
    **/
    public function testDisconnect()
    {
        $this->markTestIncomplete('testDisconnect is not implemented yet');
    }

    /**
    * Test for Connection::__destruct() 
    *
    **/
    public function test__destruct()
    {
        $this->markTestIncomplete('test__destruct is not implemented yet');
    }

}
