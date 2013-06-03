<?php

namespace Tests\Icinga\Web;
/**
*
* Test class for Hook 
* Created Fri, 22 Mar 2013 09:44:40 +0000 
*
**/
require_once("../library/Icinga/Exception/ProgrammingError.php");
require_once("../library/Icinga/Web/Hook.php");

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


class HookTest extends \PHPUnit_Framework_TestCase
{

    /**
    * Test for Hook::Has() 
    * Note: This method is static! 
    *
    **/
    public function testHas()
    {
        Hook::clean();
        $this->assertFalse(Hook::has("a"));
        $this->assertFalse(Hook::has("a","b"));

        Hook::register("a","b","c");
        $this->assertTrue(Hook::has("a"));
        $this->assertTrue(Hook::has("a","b"));
        Hook::clean();
    }

    /**
    * Test for Hook::CreateInstance() 
    * Note: This method is static! 
    *
    **/
    public function testCreateInstance()
    {
        Hook::clean();
        Hook::$BASE_NS = "Tests\\Icinga\\Web\\";
        Hook::register("Base","b","Tests\\Icinga\\Web\\TestHookImplementation");
        $this->assertInstanceOf("Tests\\Icinga\\Web\\TestHookImplementation",Hook::createInstance("Base","b"));
        Hook::clean();
    }

    /**
     * Test for Hook::CreateInstance()
     * Note: This method is static!
     *
     *
     **/
    public function testCreateInvalidInstance()
    {
        $this->setExpectedException('\Icinga\Exception\ProgrammingError');
        Hook::clean();
        Hook::$BASE_NS = "Tests\\Icinga\\Web\\";
        Hook::register("Base","b","Tests\\Icinga\\Web\\TestBadHookImplementation");
        Hook::createInstance("Base","b");
        Hook::clean();
    }

    /**
    * Test for Hook::All() 
    * Note: This method is static! 
    *
    **/
    public function testAll()
    {
        Hook::clean();
        Hook::$BASE_NS = "Tests\\Icinga\\Web\\";
        Hook::register("Base","a","Tests\\Icinga\\Web\\TestHookImplementation");
        Hook::register("Base","b","Tests\\Icinga\\Web\\TestHookImplementation");
        Hook::register("Base","c","Tests\\Icinga\\Web\\TestHookImplementation");
        $this->assertCount(3,Hook::all("Base"));
        foreach(Hook::all("Base") as $instance) {
            $this->assertInstanceOf("Tests\\Icinga\\Web\\TestHookImplementation",$instance);
        }
        Hook::clean();
    }

    /**
    * Test for Hook::First() 
    * Note: This method is static! 
    *
    **/
    public function testFirst()
    {
        Hook::clean();
        Hook::$BASE_NS = "Tests\\Icinga\\Web\\";
        Hook::register("Base","a","Tests\\Icinga\\Web\\TestHookImplementation");
        Hook::register("Base","b","Tests\\Icinga\\Web\\TestHookImplementation");
        Hook::register("Base","c","Tests\\Icinga\\Web\\TestHookImplementation");

        $this->assertInstanceOf("Tests\\Icinga\\Web\\TestHookImplementation",Hook::first("Base"));
        Hook::clean();
    }

}
