<?php

namespace Tests\Icinga\Protocol\Statusdat;

require_once("../../library/Icinga/Protocol/Statusdat/RuntimeStateContainer.php");

class RuntimestatecontainerTest extends \PHPUnit_Framework_TestCase
{

    /**
    * Test for RuntimeStateContainer::__get()
    *
    **/
    public function testPropertyResolving()
    {

        $container = new \Icinga\Protocol\Statusdat\RuntimeStateContainer("
            host_name=test host
            current_state=0
            plugin_output=test 1234 test test
            test=dont read
        ");
        $container->test = "test123";
        $this->assertEquals("test host",$container->host_name);
        $this->assertEquals($container->test,"test123");
        $this->assertEquals(0,$container->current_state);

    }

}
