<?php

namespace Tests\Icinga\Protocol\Livestatus;

use Icinga\Protocol\Livestatus\Connection;
use Icinga\Protocol\Livestatus\Query;
use PHPUnit_Framework_TestCase as TestCase;

require_once('../../library/Icinga/Protocol/BaseQuery.php');
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

}
