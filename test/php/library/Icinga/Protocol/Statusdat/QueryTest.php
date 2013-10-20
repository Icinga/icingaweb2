<?php

namespace Tests\Icinga\Protocol\Statusdat;
require_once('../../library/Icinga/Filter/Filterable.php');
require_once('../../library/Icinga/Data/BaseQuery.php');
require_once('../../library/Icinga/Protocol/Statusdat/Query.php');
require_once(dirname(__FILE__)."/ReaderMock.php");

use Icinga\Protocol\Statusdat as Statusdat;

/**
 *
 * Test class for Query
 * Created Wed, 16 Jan 2013 15:15:16 +0000
 *
 **/
class QueryTest extends \PHPUnit_Framework_TestCase
{

    public function testSimpleServiceSelect()
    {
        $readerMock = $this->getServiceTestReader();
        $query = new Statusdat\Query($readerMock);
        $objects = $readerMock->getObjects();

        $result = $query->select()->from("services")->getResult();
        $this->assertCount(count($objects["service"]), $result);
    }

    public function testSimpleHostSelect()
    {
        $readerMock = $this->getServiceTestReader();
        $query = new Statusdat\Query($readerMock);
        $objects = $readerMock->getObjects();

        $result = $query->from("hosts")->getResult();
        $this->assertCount(count($objects["host"]), $result);

    }

    public function testLimit()
    {
        $readerMock = $this->getServiceTestReader();
        $objects = $readerMock->getObjects();
        $query = new Statusdat\Query($readerMock);

        $result = $query->from("services")->limit(2)->getResult();
        $this->assertCount(2, $result);

    }

    public function testOffset()
    {
        $readerMock = $this->getServiceTestReader();
        $objects = $readerMock->getObjects();
        $query = new Statusdat\Query($readerMock);

        $result = $query->from("services")->limit(2, 4)->getResult();
        $this->assertCount(2, $result);

    }

    public function testGroupByColumn()
    {
        $readerMock = $this->getServiceTestReader();
        $objects = $readerMock->getObjects();
        $query = new Statusdat\Query($readerMock);
        $result = $query->from("services")->groupByColumns("numeric_val")->getResult();
        $this->assertCount(3,$result);
        foreach($result as $value) {
            $this->assertTrue(isset($value->count));
            $this->assertTrue(isset($value->columns));
            $this->assertEquals(2,$value->count);
        }

    }

    public function testOrderedGroupByColumn()
    {
        $readerMock = $this->getServiceTestReader();
        $objects = $readerMock->getObjects();
        $query = new Statusdat\Query($readerMock);
        $result = $query->from("services")->order('numeric_val ASC')->groupByColumns("numeric_val")->getResult();
        $this->assertCount(3,$result);
        foreach($result as $sstatus) {
            $this->assertTrue(isset($sstatus->count));
            $this->assertTrue(isset($sstatus->columns));
            $this->assertEquals(2, $sstatus->count);

        }

    }

    private function getServiceTestReader()
    {
        $readerMock = new ReaderMock(array(
            "host" => array(
                "hosta" => (object) array(
                    "host_name" => "hosta",
                    "numeric_val" => 0,
                    "services" => array(0, 1, 2)
                ),
                "hostb" => (object) array(
                    "host_name" => "hostb",
                    "numeric_val" => 0,
                    "services" => array(3, 4, 5)
                )
            ),
            "service" => array(
                "hosta;service1" => (object) array(
                    "host_name" => "hosta",
                    "service_description" => "service1",
                    "numeric_val" => 1
                ),
                "hosta;service2" => (object) array(
                    "host_name" => "hosta",
                    "service_description" => "service2",
                    "numeric_val" => 3
                ),
                "hosta;service3" => (object) array(
                    "host_name" => "hosta",
                    "service_description" => "service3",
                    "numeric_val" => 2
                ),
                "hostb;service1" => (object) array(
                    "host_name" => "hostb",
                    "service_description" => "service1",
                    "numeric_val" => 1
                ),
                "hostb;service2" => (object) array(
                    "host_name" => "hostb",
                    "service_description" => "service2",
                    "numeric_val" => 3
                ),
                "hostb;service3" => (object) array(
                    "host_name" => "hostb",
                    "service_description" => "service3",
                    "numeric_val" => 2
                )
            )
        ));
        return $readerMock;
    }
}
