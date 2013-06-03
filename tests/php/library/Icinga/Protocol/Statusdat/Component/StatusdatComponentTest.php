<?php

namespace Tests\Icinga\Protocol\Statusdat;
require_once("Zend/Config.php");
require_once("Zend/Log.php");

require_once("../../library/Icinga/Protocol/Statusdat/IReader.php");
require_once("../../library/Icinga/Protocol/Statusdat/Reader.php");
require_once("../../library/Icinga/Protocol/Statusdat/Exception/ParsingException.php");
require_once("../../library/Icinga/Exception/ProgrammingError.php");
require_once("../../library/Icinga/Protocol/Statusdat/Parser.php");
require_once("../../library/Icinga/Protocol/AbstractQuery.php");
require_once("../../library/Icinga/Protocol/Statusdat/Query.php");
require_once("../../library/Icinga/Protocol/Statusdat/Query/IQueryPart.php");
require_once("../../library/Icinga/Protocol/Statusdat/Query/Group.php");
require_once("../../library/Icinga/Protocol/Statusdat/Query/Expression.php");
require_once("../../library/Icinga/Exception/ConfigurationError.php");
require_once("../../library/Icinga/Application/Logger.php");
use  \Icinga\Protocol\Statusdat as SD;

/**
 * This is a high level test for the whole statusdat component, i.e. all parts put together
 * and called like they would be in a real situation. This should work when all isolated tests have passed.
 */
class StatusdatComponentTest extends \PHPUnit_Framework_TestCase
{
    public function getReader() {
        $reader = new SD\Reader(new \Zend_Config(array(
            "status_file" => dirname(__FILE__)."/status.dat",
            "objects_file" => dirname(__FILE__)."/objects.cache"
        )),null,true);
        return $reader;
    }

    public function testServicegroupFilterFromService() {
        $r = $this->getReader();
        $group = array(array('a1','b2'));
        $result = $r->select()->from("services")->where("group IN ?",$group)->getResult();
        $this->assertCount(2,$result);
        foreach($result as $obj) {
            $this->assertTrue(is_object($obj));
        }
    }

    public function testServicegroupFilterFromHost() {
        $r = $this->getReader();
        $group = array(array('a1','b2'));
        $result = $r->select()->from("hosts")->where("services.group IN ?",$group)->getResult();
        $this->assertCount(2,$result);
        foreach($result as $obj) {
            $this->assertTrue(is_object($obj));
        }
    }

    public function testHostgroupFilterFromHost() {
        $r = $this->getReader();
        $group = array(array('exc-hostb'));
        $result = $r->select()->from("hosts")->where("group IN ?",$group)->getResult();
        $this->assertCount(2,$result);
        foreach($result as $obj) {
            $this->assertTrue(is_object($obj));
        }
    }

    public function testHostgroupFilterFromService() {
        $r = $this->getReader();
        $group = array(array('exc-hostb'));
        $result = $r->select()->from("services")->where("host.group IN ?",$group)->getResult();

        $this->assertCount(6,$result);
        foreach($result as $obj) {
            $this->assertTrue(is_object($obj));
        }
    }
}
