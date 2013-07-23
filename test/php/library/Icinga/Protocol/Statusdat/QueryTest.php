<?php

namespace Tests\Icinga\Protocol\Statusdat;
require_once("../../library/Icinga/Data/AbstractQuery.php");
require_once("../../library/Icinga/Protocol/Statusdat/Query.php");
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

        $result = $query->from("services")->getResult();
        $objects = $readerMock->getObjects();
        $this->assertCount(count($objects["service"]), $result);

    }

    public function testSimpleHostSelect()
    {
        $readerMock = $this->getServiceTestReader();
        $query = new Statusdat\Query($readerMock);

        $result = $query->from("hosts")->getResult();
        $objects = $readerMock->getObjects();
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
        $lastIdx = ~PHP_INT_MAX;
        foreach($result as $sstatus) {
            $this->assertTrue(isset($sstatus->count));
            $this->assertTrue(isset($sstatus->columns));
            $this->assertEquals(2,$sstatus->count);
            $this->assertGreaterThanOrEqual($lastIdx,$sstatus->columns->numeric_val);
            $lastIdx = $sstatus->columns->numeric_val;


        }

    }

    public function testOrderSingleColumnASC()
    {
        $readerMock = $this->getServiceTestReader();
        $objects = $readerMock->getObjects();
        $query = new Statusdat\Query($readerMock);
        $result = $query->from("services")->order('numeric_val ASC')->getResult();
        $lastIdx = ~PHP_INT_MAX;
        foreach($result as $sstatus) {
            $this->assertGreaterThanOrEqual($lastIdx,$sstatus->numeric_val);
            $lastIdx = $sstatus->numeric_val;
        }
    }

    public function testOrderSingleColumnDESC()
    {
        $readerMock = $this->getServiceTestReader();
        $objects = $readerMock->getObjects();
        $query = new Statusdat\Query($readerMock);
        $result = $query->from("services")->order('numeric_val DESC')->getResult();
        $lastIdx = PHP_INT_MAX;
        foreach($result as $sstatus) {
            $this->assertLessThanOrEqual($lastIdx,$sstatus->numeric_val);
            $lastIdx = $sstatus->numeric_val;
        }
    }

    /**
     * Integration test for query and Expression/Group objects.
     * This is not a unit test, but checks if the 'where' filter really works
     */
    public function testQueryIntegration() {

        $readerMock = $this->getServiceTestReader();
        $objects = $readerMock->getObjects();
        $query = new Statusdat\Query($readerMock);
        $result = $query->from("services")->where('numeric_val = ?',array(1))->getResult();
        foreach($result as $testresult) {
            $this->assertEquals($testresult->numeric_val,1);
        }
        $query = new Statusdat\Query($readerMock);
        $result = $query->from("services")->where('numeric_val < ? OR numeric_val = ?',array(2,3))->getResult();
        foreach($result as $testresult) {
            $this->assertNotEquals($testresult->numeric_val,2);
        }
        $query = new Statusdat\Query($readerMock);
        $result = $query->from("services")->where('numeric_val < ? OR numeric_val = ?',array(2,3))->where("numeric_val = ?",array(1))->getResult();
        foreach($result as $testresult) {
            $this->assertEquals($testresult->numeric_val,1);
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
