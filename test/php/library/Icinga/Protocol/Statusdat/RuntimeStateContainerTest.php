<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Protocol\Statusdat;

use Icinga\Test\BaseTestCase;
use Icinga\Protocol\Statusdat\RuntimeStateContainer;

class RuntimestatecontainerTest extends BaseTestCase
{
    public function testPropertyResolving()
    {
        $container = new RuntimeStateContainer("
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
