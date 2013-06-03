<?php

namespace Tests\Icinga\Protocol\Statusdat;
require_once("../library/Icinga/Protocol/Statusdat/IReader.php");
require_once("../library/Icinga/Protocol/Statusdat/Reader.php");
require_once("../library/Icinga/Exception/ConfigurationError.php");

use Icinga\Protocol\Statusdat\Reader as Reader;
define("APPLICATION_PATH","./"); // TODO: test boostrap
/**
*
* Test class for Reader 
* Created Wed, 16 Jan 2013 15:15:16 +0000 
*
**/
class ConfigMock {
    function __construct($data) {
        foreach($data as $key=>$val)
            $this->$key = $val;
    }
    function get($attr) {
        return $this->$attr;
    }
}

class ParserMock {

    public $runtime = array();
    public $objects = array();
    public function parseObjectsFile() {
        return $this->objects;
    }
    public function parseRuntimeState() {
        return $this->runtime;
    }

    public function getRuntimeState() {
        return $this->runtime;
    }
}

require("Zend/Cache.php");
class ReaderTest extends \PHPUnit_Framework_TestCase
{

    public function testFileCaching() {
        $parser = new ParserMock();
        @system("rm ./tmp/zend_cache*");
        $parser->runtime = array("host"=>array(
            "test" => (object) array(
                "host_name" => "test"
            )
        ));
        $object_file = tempnam("./dir","object");
        $status_file = tempnam("./dir","status");
        $reader = new Reader(new ConfigMock(array(
            "cache_path" => "./tmp",
            "objects_file" => $object_file,
            "status_file" => $status_file
        )),$parser);
        unlink($object_file);
        unlink($status_file);
        $this->assertTrue(file_exists("./tmp/zend_cache---objects".md5($object_file)));
        $this->assertTrue(file_exists("./tmp/zend_cache---state".md5($object_file)));
        system("rm ./tmp/zend_cache*");
    }
    public function testEmptyFileException() {
        $this->setExpectedException("Icinga\Exception\ConfigurationError");
        $parser = new ParserMock();
        $reader = new Reader(new ConfigMock(array(
            "cache_path" => "./tmp",
            "objects_file" => "",
            "status_file" => "",
        )),$parser);
    }
}
