<?php

namespace Tests\Icinga\Protocol\Statusdat;
require_once("Zend/Config.php");;
require_once("Zend/Log.php");;

use  \Icinga\Protocol\Statusdat as SD;

/**
 * This is a high level test for the whole statusdat component, i.e. all parts put together
 * and called like they would be in a real situation. This should work when all isolated tests have passed.
 */
class StatusdatComponentTest extends \PHPUnit_Framework_TestCase
{
    public function getReader() {
        require_once(dirname(__FILE__)."/../StatusdatTestLoader.php");
        StatusdatTestLoader::requireLibrary();
        $reader = new SD\Reader(new \Zend_Config(array(
            "status_file" => dirname(__FILE__)."/status.dat",
            "object_file" => dirname(__FILE__)."/objects.cache"
        )),null,true);
        return $reader;
    }

    public function testServicegroupFilterFromService() {
        $r = $this->getReader();
        $group = array(array('a1','b2'));
        $result = $r->select()->from("services")->where("group IN ?",$group)->getResult();

        $this->assertCount(9, $result, 'Assert items to be returned in a servicegroup filter');
        foreach($result as $obj) {
            $this->assertTrue(is_object($obj));
        }
    }

    public function testServicegroupFilterFromHost() {
        $r = $this->getReader();
        $group = array(array('a1','b2'));
        $result = $r->select()->from("hosts")->where("services.group IN ?",$group)->getResult();
        $this->assertCount(3, $result);
        foreach($result as $obj) {
            $this->assertTrue(is_object($obj));
        }
    }

    public function testHostgroupFilterFromHost() {
        $r = $this->getReader();
        $group = array(array('exc-hostb'));
        $result = $r->select()->from("hosts")->where("group IN ?",$group)->getResult();
        $this->assertCount(3, $result);
        foreach($result as $obj) {
            $this->assertTrue(is_object($obj));
        }
    }

    public function testHostgroupFilterFromService() {
        $r = $this->getReader();
        $group = array(array('exc-hostb'));
        $result = $r->select()->from("services")->where("host.group IN ?",$group)->getResult();

        $this->assertCount(9, $result);
        foreach($result as $obj) {
            $this->assertTrue(is_object($obj));
        }
    }
}
