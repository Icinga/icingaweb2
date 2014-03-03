<?php

namespace Tests\Icinga\Protocol\Statusdat;

require_once("StatusdatTestLoader.php");

use Icinga\Protocol\Statusdat\Reader as Reader;

StatusdatTestLoader::requireLibrary();

if (!defined('APPLICATION_PATH')) {
    define("APPLICATION_PATH", "./"); // TODO: test boostrap
}
/**
 *
 * Test class for Reader
 * Created Wed, 16 Jan 2013 15:15:16 +0000
 *
 **/
class ConfigMock
{
    function __construct($data)
    {
        foreach ($data as $key => $val) {
            $this->$key = $val;
        }
    }

    function get($attr)
    {
        return $this->$attr;
    }
}

class ParserMock
{

    public $runtime = array();
    public $objects = array();

    public function parseObjectsFile()
    {
        return $this->objects;
    }

    public function parseRuntimeState()
    {
        return $this->runtime;
    }

    public function getRuntimeState()
    {
        return $this->runtime;
    }
}

class ReaderTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        if (file_exists('./tmp')) {
            @system("rm -rf ./tmp");
        }
    }

    public function testFileCaching()
    {
        if (!file_exists('./tmp')) {
            mkdir('./tmp');
        }
        $parser = new ParserMock();
        $parser->runtime = array(
            "host" => array(
                "test" => (object)array(
                    "host_name" => "test"
                )
            )
        );
        $object_file = tempnam("./dir", "object");
        $status_file = tempnam("./dir", "status");
        $reader = new Reader(new ConfigMock(array(
            "cache_path" => "/tmp",
            "object_file" => $object_file,
            "status_file" => $status_file
        )), $parser);
        unlink($object_file);
        unlink($status_file);
        $this->assertTrue(file_exists("/tmp/zend_cache---object" . md5($object_file)));
        $this->assertTrue(file_exists("/tmp/zend_cache---state" . md5($object_file)));
        system("rm /tmp/zend_cache*");
    }

    public function testEmptyFileException()
    {

        $this->setExpectedException("Icinga\Exception\ConfigurationError");
        $parser = new ParserMock();
        $reader = new Reader(new ConfigMock(array(
            "cache_path" => "/tmp",
            "object_file" => "",
            "status_file" => "",
        )), $parser);
    }
}
