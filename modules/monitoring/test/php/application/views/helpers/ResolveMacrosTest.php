<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2014 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2014 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Test\Modules\Monitoring\Application\Views\Helpers;

require_once realpath(__DIR__ . '/../../../../../../../library/Icinga/Test/BaseTestCase.php');

use Icinga\Test\BaseTestCase;

require_once 'Zend/View/Helper/Abstract.php';
require_once BaseTestCase::$moduleDir . '/monitoring/application/views/helpers/ResolveMacros.php';

use \stdClass;

class ResolveMacrosTest extends BaseTestCase
{
    public function testHostMacros()
    {
        $hostMock = new stdClass();
        $hostMock->host_name = 'test';
        $hostMock->host_address = '1.1.1.1';

        $helper = new \Zend_View_Helper_ResolveMacros();
        $this->assertEquals($helper->resolveMacros('$HOSTNAME$', $hostMock), $hostMock->host_name);
        $this->assertEquals($helper->resolveMacros('$HOSTADDRESS$', $hostMock), $hostMock->host_address);
    }

    public function testServiceMacros()
    {
        $svcMock = new stdClass();
        $svcMock->host_name = 'test';
        $svcMock->host_address = '1.1.1.1';
        $svcMock->service_description = 'a service';

        $helper = new \Zend_View_Helper_ResolveMacros();
        $this->assertEquals($helper->resolveMacros('$HOSTNAME$', $svcMock), $svcMock->host_name);
        $this->assertEquals($helper->resolveMacros('$HOSTADDRESS$', $svcMock), $svcMock->host_address);
        $this->assertEquals($helper->resolveMacros('$SERVICEDESC$', $svcMock), $svcMock->service_description);
    }

    public function testCustomvars()
    {
        $objectMock = new stdClass();
        $objectMock->customvars = array(
            'CUSTOMVAR' => 'test'
        );

        $helper = new \Zend_View_Helper_ResolveMacros();
        $this->assertEquals($helper->resolveMacros('$CUSTOMVAR$', $objectMock), $objectMock->customvars['CUSTOMVAR']);
    }

    public function testFaultyMacros()
    {
        $hostMock = new \stdClass();
        $hostMock->host_name = 'test';
        $hostMock->customvars = array(
            'HOST' => 'te',
            'NAME' => 'st'
        );

        $helper = new \Zend_View_Helper_ResolveMacros();
        $this->assertEquals(
            $helper->resolveMacros('$$HOSTNAME$ $ HOSTNAME$ $HOST$NAME$', $hostMock),
            '$test $ HOSTNAME$ teNAME$'
        );
    }

    public function testMacrosWithSpecialCharacters()
    {
        $objectMock = new \stdClass();
        $objectMock->customvars = array(
            'V€RY_SP3C|@L' => 'not too special!'
        );

        $helper = new \Zend_View_Helper_ResolveMacros();
        $this->assertEquals(
            $helper->resolveMacros('$V€RY_SP3C|@L$', $objectMock),
            $objectMock->customvars['V€RY_SP3C|@L']
        );
    }
}
