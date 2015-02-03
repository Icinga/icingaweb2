<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Tests\Icinga\Modules\Monitoring\Application\Views\Helpers;

use Mockery;
use Zend_View_Helper_ResolveMacros;
use Icinga\Test\BaseTestCase;

require_once realpath(BaseTestCase::$moduleDir . '/monitoring/application/views/helpers/ResolveMacros.php');

class ResolveMacrosTest extends BaseTestCase
{
    public function testHostMacros()
    {
        $hostMock = Mockery::mock('host');
        $hostMock->host_name = 'test';
        $hostMock->host_address = '1.1.1.1';

        $helper = new Zend_View_Helper_ResolveMacros();
        $this->assertEquals($helper->resolveMacros('$HOSTNAME$', $hostMock), $hostMock->host_name);
        $this->assertEquals($helper->resolveMacros('$HOSTADDRESS$', $hostMock), $hostMock->host_address);
    }

    public function testServiceMacros()
    {
        $svcMock = Mockery::mock('service');
        $svcMock->host_name = 'test';
        $svcMock->host_address = '1.1.1.1';
        $svcMock->service_description = 'a service';

        $helper = new Zend_View_Helper_ResolveMacros();
        $this->assertEquals($helper->resolveMacros('$HOSTNAME$', $svcMock), $svcMock->host_name);
        $this->assertEquals($helper->resolveMacros('$HOSTADDRESS$', $svcMock), $svcMock->host_address);
        $this->assertEquals($helper->resolveMacros('$SERVICEDESC$', $svcMock), $svcMock->service_description);
    }

    public function testCustomvars()
    {
        $objectMock = Mockery::mock('object');
        $objectMock->customvars = array(
            'CUSTOMVAR' => 'test'
        );

        $helper = new Zend_View_Helper_ResolveMacros();
        $this->assertEquals($helper->resolveMacros('$CUSTOMVAR$', $objectMock), $objectMock->customvars['CUSTOMVAR']);
    }

    public function testFaultyMacros()
    {
        $hostMock = Mockery::mock('host');
        $hostMock->host_name = 'test';
        $hostMock->customvars = array(
            'HOST' => 'te',
            'NAME' => 'st'
        );

        $helper = new Zend_View_Helper_ResolveMacros();
        $this->assertEquals(
            $helper->resolveMacros('$$HOSTNAME$ $ HOSTNAME$ $HOST$NAME$', $hostMock),
            '$test $ HOSTNAME$ teNAME$'
        );
    }

    public function testMacrosWithSpecialCharacters()
    {
        $objectMock = Mockery::mock('object');
        $objectMock->customvars = array(
            'V€RY_SP3C|@L' => 'not too special!'
        );

        $helper = new Zend_View_Helper_ResolveMacros();
        $this->assertEquals(
            $helper->resolveMacros('$V€RY_SP3C|@L$', $objectMock),
            $objectMock->customvars['V€RY_SP3C|@L']
        );
    }
}
