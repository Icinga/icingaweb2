<?php

namespace Tests\Icinga\Protocol\Statusdat;
require_once(realpath("../../library/Icinga/Protocol/Statusdat/Exception/ParsingException.php"));
require_once(realpath("../../library/Icinga/Exception/ProgrammingError.php"));
require_once(realpath("../../library/Icinga/Protocol/Statusdat/Parser.php"));
use Icinga\Protocol\Statusdat\Parser;
/**
*
* Test class for Parser
* Created Wed, 16 Jan 2013 15:15:16 +0000
*
**/
class ParserTest extends \PHPUnit_Framework_TestCase
{

    private function getStringAsFileHandle($string)
    {
        $maxsize = strlen($string)*2;
        $fhandle = fopen("php://memory", 'r+');
        fputs($fhandle,$string);
        rewind($fhandle);
        return $fhandle;
    }

    public function testSimpleObjectCacheParsing()
    {
        $fd = $this->getStringAsFileHandle("
define hostescalation {
    host_name\ttest
    key\tvalue
}

define host {
    host_name\ttest
    alias\ttest123
}

define host {
    host_name\ttest2
    alias\ttest123
}

define service {
    host_name\ttest
    service_description\tCurrent Users
}

define servicegroup {
    servicegroup_name\tgroup
    members\ttest,Current Users
}
        ");
        $testParser = new Parser($fd);
        $testParser->parseObjectsFile();
        $state = $testParser->getRuntimeState();
        $this->assertTrue(is_array($state));
        $this->assertTrue(isset($state["host"]));
        $this->assertTrue(isset($state["service"]));
        $this->assertEquals("test",$state["host"]["test"]->host_name);
        $this->assertTrue(is_array($state["host"]["test"]->escalation));
        $this->assertTrue(isset($state["service"]["test;Current Users"]->group));
        $this->assertTrue(is_array($state["service"]["test;Current Users"]->group));
        $this->assertCount(1,$state["service"]["test;Current Users"]->group);
        $this->assertEquals("group",$state["service"]["test;Current Users"]->group[0]);
        $this->assertEquals("value",$state["host"]["test"]->escalation[0]->key);
        $this->assertEquals("test2",$state["host"]["test2"]->host_name);
    }

    public function testRuntimeParsing()
    {
        $baseState = array(
            "host" => array(
                "test" => (object) array(
                    "host_name" => "test"
                ),
                "test2" => (object) array(
                    "host_name" => "test2"
                )
            ),
            "service" => array(
                "test;Current Users" => (object) array(
                    "host_name" => "test",
                    "service_description" => "Current Users"
                )
            )
        );
        $fd = $this->getStringAsFileHandle(self::RUNTIME_STATE1);

        $testParser = new Parser($fd, $baseState);
        $testParser->parseRuntimeState();
        $state = $testParser->getRuntimeState();

        $this->assertTrue(isset($state["host"]["test"]->status));
        $this->assertEquals(3,$state["host"]["test"]->status->current_state);

        $this->assertTrue(is_array($state["host"]["test"]->comment));
        $this->assertEquals(2,count($state["host"]["test"]->comment));
    }

    public function testOverwriteRuntime()
    {
        $baseState = array(
            "host" => array(
                "test" => (object) array(
                    "host_name" => "test"
                ),
                "test2" => (object) array(
                    "host_name" => "test2"
                )
            ),
            "service" => array(
                "test;Current Users" => (object) array(
                    "host_name" => "test",
                    "service_description" => "Current Users"
                )
            )
        );
        $fd = $this->getStringAsFileHandle(self::RUNTIME_STATE1);

        $testParser = new Parser($fd, $baseState);
        $testParser->parseRuntimeState();
        $state = $testParser->getRuntimeState();

        $this->assertTrue(isset($state["host"]["test"]->status));
        $this->assertEquals(3,$state["host"]["test"]->status->current_state);

        $this->assertTrue(is_array($state["host"]["test"]->comment));
        $this->assertEquals(2,count($state["host"]["test"]->comment));

        $fd = $this->getStringAsFileHandle(self::RUNTIME_STATE2);
        $testParser->parseRuntimeState($fd);
        $state = $testParser->getRuntimeState();

        $this->assertTrue(isset($state["host"]["test"]->status));
        $this->assertEquals(2,$state["host"]["test"]->status->current_state);
        $this->assertTrue(is_array($state["host"]["test"]->comment));
        $this->assertEquals(3,count($state["host"]["test"]->comment));

    }

    /**
     * Assert no errors occuring
     */
    public function testRuntimeParsingForBigFile()
    {
        //$this->markTestSkipped('Skipped slow tests');
        $objects = fopen("./res/status/icinga.objects.cache","r");
        $status = fopen("./res/status/icinga.status.dat","r");
        $testParser = new Parser($objects);
        $testParser->parseObjectsFile();
        $testParser->parseRuntimeState($status);
    }

    const RUNTIME_STATE1 = "

hoststatus {
    host_name=test
    current_state=3
    test=test123
}

hoststatus {
    host_name=test2
    current_state=3
    test=test123
}

servicestatus {
    host_name=test
    service_description=Current Users
    current_state=3
}

hostcomment {
    host_name=test
    key=value1
}

hostcomment {
    host_name=test
    key=value2
}";
    const RUNTIME_STATE2 = "

hoststatus {
    host_name=test
    current_state=2
    test=test123
}

hoststatus {
    host_name=test2
    current_state=2
    test=test123
}

servicestatus {
    host_name=test
    service_description=Current Users
    current_state=2
}

hostcomment {
    host_name=test
    key=value14
}
hostcomment {
    host_name=test
    key=value15
}

hostcomment {
    host_name=test
    key=value24
}";

}
