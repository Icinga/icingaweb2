<?php


namespace Tests\Icinga\Application;

require_once("Zend/Log.php");
require_once("Zend/Config.php");
require_once("Zend/Log/Writer/Mock.php");
require_once("Zend/Log/Writer/Null.php");
require_once("Zend/Log/Filter/Priority.php");

require_once("../../library/Icinga/Application/Logger.php");
require_once("../../library/Icinga/Exception/ConfigurationError.php");

use \Icinga\Application\Logger as Logger;
/**
*
* Test class for Logger 
* Created Thu, 07 Feb 2013 10:07:13 +0000 
*
**/
class LoggerTest extends \PHPUnit_Framework_TestCase
{

    public function testOverwrite() {
        $cfg1 = new \Zend_Config(array(
            "debug"     => array("enable" => 0),
            "type"      => "mock",
            "target"    => "target2"
        ));
        $cfg2 = new \Zend_Config(array(
            "debug"     => array(
                "enable" => 1,
                "type"=>"mock",
                "target"=>"target3"
            ),
            "type"      => "mock",
            "target"    => "target4"
        ));

        $logger = new Logger($cfg1);
        $writers = $logger->getWriters();
        $this->assertEquals(1,count($writers));

        $logger = new Logger($cfg1);
        $writers2 = $logger->getWriters();
        $this->assertEquals(1,count($writers));
        $this->assertNotEquals($writers[0],$writers2[0]);

        $logger = new Logger($cfg2);
        $writers2 = $logger->getWriters();
        $this->assertEquals(2,count($writers2));
    }


    public function testFormatMessage() {
        $message = Logger::formatMessage(array("Testmessage"));
        $this->assertEquals("Testmessage",$message);

        $message = Logger::formatMessage(array("Testmessage %s %s","test1","test2"));
        $this->assertEquals("Testmessage test1 test2",$message);

        $message = Logger::formatMessage(array("Testmessage %s",array("test1","test2")));
        $this->assertEquals("Testmessage ".json_encode(array("test1","test2")),$message);
    }


    public function testLoggingOutput() {
        $cfg1 = new \Zend_Config(array(
            "debug"     => array("enable" => 0),
            "type"      => "mock",
            "target"    => "target2"
        ));
        Logger::reset();
        $logger = Logger::create($cfg1);
        $writers = $logger->getWriters();

        $logger->warn("Warning");
        $logger->error("Error");
        $logger->info("Info");
        $logger->debug("Debug");

        $writer = $writers[0];
        $this->assertEquals(2,count($writer->events));
        $this->assertEquals($writer->events[0]["message"],"Warning");
        $this->assertEquals($writer->events[1]["message"],"Error");
        Logger::reset();
    }

    public function testLogQueuing() {
        $cfg1 = new \Zend_Config(array(
            "debug"     => array("enable" => 0),
            "type"      => "mock",
            "target"    => "target2"
        ));

        Logger::reset();
        Logger::warn("Warning");
        Logger::error("Error");
        Logger::info("Info");
        Logger::debug("Debug");

        $logger = Logger::create($cfg1);
        $writers = $logger->getWriters();
        $writer = $writers[0];

        $this->assertEquals(2,count($writer->events));
        $this->assertEquals($writer->events[0]["message"],"Warning");
        $this->assertEquals($writer->events[1]["message"],"Error");
        Logger::reset();
    }

    public function testDebugLogErrorCatching()
    {
        $cfg1 = new \Zend_Config(array(
            "debug"     => array(
                "enable"   => 1,
                "type"      => 'Invalid',
                "target"    => "..."
            ),
            "type"      => "mock",
            "target"    => "target2"
        ));
        Logger::reset();
        $logger = Logger::create($cfg1);
        $writers = $logger->getWriters();
        $this->assertEquals(1,count($writers));
        $this->assertEquals(1,count($writers[0]->events));
        $exceptionStart = "Could not create debug log:";
        $this->assertEquals(substr($writers[0]->events[0]["message"],0,strlen($exceptionStart)),$exceptionStart);
        Logger::reset();
    }
    /**
     * @expectedException \Icinga\Exception\ConfigurationError
     */
    public function testGeneralLogException() {
        $cfg1 = new \Zend_Config(array(
            "debug"     => array(
                "enable"   => 0,
                "type"      => 'Invalid',
                "target"    => "..."
            ),
            "type"      => "invalid",
            "target"    => "target2"

        ));
        Logger::reset();
        $logger = Logger::create($cfg1);

        Logger::reset();
    }

}
