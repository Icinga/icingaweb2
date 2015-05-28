<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Modules\Monitoring\Application\Views\Helpers;

use Mockery;
use Icinga\Test\BaseTestCase;
use Icinga\Module\Monitoring\Object\Macro;

require_once realpath(BaseTestCase::$moduleDir . '/monitoring/library/Monitoring/Object/Macro.php');

class MacroTest extends BaseTestCase
{
    public function testHostMacros()
    {
        $hostMock = Mockery::mock('host');
        $hostMock->host_name = 'test';
        $hostMock->host_address = '1.1.1.1';

        $this->assertEquals(Macro::resolveMacros('$HOSTNAME$', $hostMock), $hostMock->host_name);
        $this->assertEquals(Macro::resolveMacros('$HOSTADDRESS$', $hostMock), $hostMock->host_address);
        $this->assertEquals(Macro::resolveMacros('$host.name$', $hostMock), $hostMock->host_name);
        $this->assertEquals(Macro::resolveMacros('$host.address$', $hostMock), $hostMock->host_address);
    }

    public function testServiceMacros()
    {
        $svcMock = Mockery::mock('service');
        $svcMock->host_name = 'test';
        $svcMock->host_address = '1.1.1.1';
        $svcMock->service_description = 'a service';

        $this->assertEquals(Macro::resolveMacros('$HOSTNAME$', $svcMock), $svcMock->host_name);
        $this->assertEquals(Macro::resolveMacros('$HOSTADDRESS$', $svcMock), $svcMock->host_address);
        $this->assertEquals(Macro::resolveMacros('$SERVICEDESC$', $svcMock), $svcMock->service_description);
        $this->assertEquals(Macro::resolveMacros('$host.name$', $svcMock), $svcMock->host_name);
        $this->assertEquals(Macro::resolveMacros('$host.address$', $svcMock), $svcMock->host_address);
        $this->assertEquals(Macro::resolveMacros('$service.description$', $svcMock), $svcMock->service_description);
    }

    public function testCustomvars()
    {
        $objectMock = Mockery::mock('object');
        $objectMock->customvars = array(
            'CUSTOMVAR' => 'test'
        );

        $this->assertEquals(Macro::resolveMacros('$CUSTOMVAR$', $objectMock), $objectMock->customvars['CUSTOMVAR']);
    }

    public function testFaultyMacros()
    {
        $hostMock = Mockery::mock('host');
        $hostMock->host_name = 'test';
        $hostMock->customvars = array(
            'HOST' => 'te',
            'NAME' => 'st'
        );

        $this->assertEquals(
            Macro::resolveMacros('$$HOSTNAME$ $ HOSTNAME$ $HOST$NAME$', $hostMock),
            '$test $ HOSTNAME$ teNAME$'
        );
    }

    public function testMacrosWithSpecialCharacters()
    {
        $objectMock = Mockery::mock('object');
        $objectMock->customvars = array(
            'V€RY_SP3C|@L' => 'not too special!'
        );

        $this->assertEquals(
            Macro::resolveMacros('$V€RY_SP3C|@L$', $objectMock),
            $objectMock->customvars['V€RY_SP3C|@L']
        );
    }
}
