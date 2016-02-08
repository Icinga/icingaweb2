<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Module\Monitoring\Application\Views\Helpers;

use Zend_View_Helper_MonitoringFlags;
use Icinga\Test\BaseTestCase;

require_once realpath(BaseTestCase::$moduleDir . '/monitoring/application/views/helpers/MonitoringFlags.php');

class MonitoringFlagsTest extends BaseTestCase
{
    public function testHosts1()
    {
        $testArray = array(
            'passive_checks_enabled' => '0',
            'active_checks_enabled' => '0',
            'obsessing' => '1',
            'notifications_enabled' => '0',
            'event_handler_enabled' => '1',
            'flap_detection_enabled' => '1',
        );

        $monitoringFlags = new Zend_View_Helper_MonitoringFlags();
        $returnArray = $monitoringFlags->monitoringFlags((object)$testArray);

        $this->assertCount(6, $returnArray);

        $expected = array(
            'Passive Checks' => false,
            'Active Checks' => false,
            'Obsessing' => true,
            'Notifications' => false,
            'Event Handler' => true,
            'Flap Detection' => true
        );

        $this->assertEquals($expected, $returnArray);
    }

    public function testService1()
    {
        $testArray = array(
            'passive_checks_enabled' => '0',
            'active_checks_enabled' => '1',
            'obsessing' => '0',
            'notifications_enabled' => '1',
            'event_handler_enabled' => '1',
            'flap_detection_enabled' => '0',
        );

        $monitoringFlags = new Zend_View_Helper_MonitoringFlags();
        $returnArray = $monitoringFlags->monitoringFlags((object)$testArray);

        $this->assertCount(6, $returnArray);

        $expected = array(
            'Passive Checks' => false,
            'Active Checks' => true,
            'Obsessing' => false,
            'Notifications' => true,
            'Event Handler' => true,
            'Flap Detection' => false
        );

        $this->assertEquals($expected, $returnArray);
    }
}
