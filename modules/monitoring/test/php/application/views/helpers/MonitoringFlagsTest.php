<?php

namespace Test\Modules\Monitoring\Application\Views\Helpers;

require_once 'Zend/View/Helper/Abstract.php';
require_once 'Zend/View.php';
require_once __DIR__. '/../../../../../application/views/helpers/MonitoringFlags.php';

class MonitoringFlagsTest extends \PHPUnit_Framework_TestCase
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

        $monitoringFlags = new \Zend_View_Helper_MonitoringFlags();
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

        $monitoringFlags = new \Zend_View_Helper_MonitoringFlags();
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
