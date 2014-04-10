<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Protocol\Statusdat;

use Icinga\Test\BaseTestCase;
use Icinga\Protocol\Statusdat\Reader;

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

class ReaderTest extends BaseTestCase
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
        $cacheDir = realpath(dirname(__FILE__) . '/.cache');
        $object_file = tempnam("./dir", "object");
        $status_file = tempnam("./dir", "status");
        $reader = new Reader(new ConfigMock(array(
            "cache_path"    => $cacheDir,
            "object_file"   => $object_file,
            "status_file"   => $status_file
        )), $parser);
        unlink($object_file);
        unlink($status_file);
        $this->assertTrue(file_exists($cacheDir . '/zend_cache---object' . md5($object_file)));
        $this->assertTrue(file_exists($cacheDir . '/zend_cache---state' . md5($object_file)));
        system('rm ' . $cacheDir . '/zend_cache*');
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
