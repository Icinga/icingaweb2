<?php

namespace Tests\Icinga\Web;
/**
*
* Test class for Hook 
* Created Fri, 22 Mar 2013 09:44:40 +0000 
*
**/
require_once("../../library/Icinga/Exception/ProgrammingError.php");
require_once("../../library/Icinga/Web/Hook.php");

require_once("Zend/Log.php");
require_once("../../library/Icinga/Application/Logger.php");

use Icinga\Web\Hook as Hook;

class Base
{
}

class TestHookImplementation extends Base
{
}

class TestBadHookImplementation
{
}

class ErrorProneHookImplementation
{
    public function __construct()
    {
        throw new \Exception("HOOK ERROR");
    }
}

class ObjectHookImplementation
{
    private $test;

    /**
     * @param mixed $test
     */
    public function setTest($test)
    {
        $this->test = $test;
    }

    /**
     * @return mixed
     */
    public function getTest()
    {
        return $this->test;
    }


    public function __toString()
    {
        return $this->getTest();
    }
}

class HookTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
        Hook::clean();
    }

    protected function tearDown()
    {
        parent::tearDown();
        Hook::clean();
    }


    /**
    * Test for Hook::Has() 
    * Note: This method is static! 
    *
    **/
    public function testHas()
    {
        $this->assertFalse(Hook::has("a"));
        $this->assertFalse(Hook::has("a","b"));

        Hook::registerClass("a","b","c");
        $this->assertTrue(Hook::has("a"));
        $this->assertTrue(Hook::has("a","b"));
    }

    /**
    * Test for Hook::CreateInstance() 
    * Note: This method is static! 
    *
    **/
    public function testCreateInstance()
    {
        Hook::$BASE_NS = "Tests\\Icinga\\Web\\";
        Hook::registerClass("Base","b","Tests\\Icinga\\Web\\TestHookImplementation");
        $this->assertInstanceOf("Tests\\Icinga\\Web\\TestHookImplementation",Hook::createInstance("Base","b"));
        Hook::clean();
    }

    /**
     * Test for Hook::CreateInstance()
     * Note: This method is static!
     *
     *
     **/
    public function testCreateInvalidInstance1()
    {
        $this->setExpectedException('\Icinga\Exception\ProgrammingError');
        Hook::$BASE_NS = "Tests\\Icinga\\Web\\";
        Hook::registerClass("Base","b","Tests\\Icinga\\Web\\TestBadHookImplementation");
        Hook::createInstance("Base","b");
        Hook::clean();
    }

    public function testCreateInvalidInstance2()
    {
        Hook::$BASE_NS = "Tests\\Icinga\\Web\\";
        $test = Hook::createInstance("Base","NOTEXIST");
        $this->assertNull($test);
    }

    public function testCreateInvalidInstance3()
    {
        Hook::$BASE_NS = "Tests\\Icinga\\Web\\";
        Hook::register("Base","ErrorProne","Tests\\Icinga\\Web\\ErrorProneHookImplementation");
        $test = Hook::createInstance("Base","ErrorProne");
        $this->assertNull($test);
    }

    /**
    * Test for Hook::All() 
    * Note: This method is static! 
    *
    **/
    public function testAll()
    {
        Hook::$BASE_NS = "Tests\\Icinga\\Web\\";
        Hook::registerClass("Base","a","Tests\\Icinga\\Web\\TestHookImplementation");
        Hook::registerClass("Base","b","Tests\\Icinga\\Web\\TestHookImplementation");
        Hook::registerClass("Base","c","Tests\\Icinga\\Web\\TestHookImplementation");
        $this->assertCount(3,Hook::all("Base"));
        foreach(Hook::all("Base") as $instance) {
            $this->assertInstanceOf("Tests\\Icinga\\Web\\TestHookImplementation",$instance);
        }

    }

    /**
    * Test for Hook::First() 
    * Note: This method is static! 
    *
    **/
    public function testFirst()
    {
        Hook::$BASE_NS = "Tests\\Icinga\\Web\\";
        Hook::registerClass("Base","a","Tests\\Icinga\\Web\\TestHookImplementation");
        Hook::registerClass("Base","b","Tests\\Icinga\\Web\\TestHookImplementation");
        Hook::registerClass("Base","c","Tests\\Icinga\\Web\\TestHookImplementation");

        $this->assertInstanceOf("Tests\\Icinga\\Web\\TestHookImplementation",Hook::first("Base"));
    }

    public function testRegisterObject()
    {
        $o1 = new ObjectHookImplementation();
        $o1->setTest('$123123');

        Hook::registerObject('Test', 'o1', $o1);

        $o2 = new ObjectHookImplementation();
        $o2->setTest('#456456');

        Hook::registerObject('Test', 'o2', $o2);

        $this->assertInstanceOf('Tests\\Icinga\\Web\\ObjectHookImplementation', Hook::createInstance('Test', 'o1'));
        $this->assertInstanceOf('Tests\\Icinga\\Web\\ObjectHookImplementation', Hook::createInstance('Test', 'o2'));

        $string = "";
        foreach (Hook::all('Test') as $hook) {
            $string .= (string)$hook;
        }
        $this->assertEquals('$123123#456456', $string);
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage object is not an instantiated class
     */
    public function testErrorObjectRegistration()
    {
        Hook::registerObject('Test', 'e1', 'STRING');
    }

    public function testGetNullHooks()
    {
        $nh = Hook::all('DOES_NOT_EXIST');
        $this->assertInternalType('array', $nh);
        $this->assertCount(0, $nh);
    }
}
