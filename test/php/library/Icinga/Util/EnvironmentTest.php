<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Util;

use Icinga\Util\Environment;
use Icinga\Test\BaseTestCase;

class EnvironmentTest extends BaseTestCase
{
    public function testRaiseMemoryLimit()
    {
        // set a low limit
        ini_set('memory_limit', '128M');

        Environment::raiseMemoryLimit('512M');
        $this->assertEquals('536870912' /* 512M */, ini_get('memory_limit'));

        Environment::raiseMemoryLimit('1G');
        $this->assertEquals('1073741824' /* 1G */, ini_get('memory_limit'));

        Environment::raiseMemoryLimit('512M');
        $this->assertEquals('1073741824' /* 1G */, ini_get('memory_limit'));

        // in phpunit usually there is no limit
        ini_set('memory_limit', '-1');
    }

    public function testRaiseExecutionTime()
    {
        Environment::raiseExecutionTime(300);
        $this->assertEquals(300, ini_get('max_execution_time'));

        Environment::raiseExecutionTime(600);
        $this->assertEquals(600, ini_get('max_execution_time'));

        Environment::raiseExecutionTime(300);
        $this->assertEquals(600, ini_get('max_execution_time'));

        // in phpunit usually there is no limit
        ini_set('max_execution_time', '0');
    }
}
