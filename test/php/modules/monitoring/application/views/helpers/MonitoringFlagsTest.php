<?php

namespace Test\Modules\Monitoring\Application\Views\Helpers;

require_once 'Zend/View/Helper/Abstract.php';
require_once 'Zend/View.php';
require_once __DIR__. '/../../../../../../../modules/monitoring/application/views/helpers/MonitoringFlags.php';

class MonitoringFlagsTest extends \PHPUnit_Framework_TestCase
{
    public function testHosts1()
    {
        $testArray = array(
            'host_passive_checks_enabled' => '0',
            'host_active_checks_enabled' => '0',
            'host_obsess_over_host' => '1',
            'host_notifications_enabled' => '0',
            'host_event_handler_enabled' => '1',
            'host_flap_detection_enabled' => '1',
        );

        $monitoringFlags = new \Zend_View_Helper_MonitoringFlags();
        $returnArray = $monitoringFlags->monitoringFlags((object)$testArray);

        $this->assertCount(6, $returnArray);

        $expected = array(
            'Passive checks' => false,
            'Active checks' => false,
            'Obsessing' => true,
            'Notifications' => false,
            'Event handler' => true,
            'Flap detection' => true
        );

        $this->assertEquals($expected, $returnArray);
    }

    public function testService1()
    {
        $testArray = array(
            'service_passive_checks_enabled' => '0',
            'service_active_checks_enabled' => '1',
            'service_obsess_over_host' => '0',
            'service_notifications_enabled' => '1',
            'service_event_handler_enabled' => '1',
            'service_flap_detection_enabled' => '0',
        );

        $monitoringFlags = new \Zend_View_Helper_MonitoringFlags();
        $returnArray = $monitoringFlags->monitoringFlags((object)$testArray);

        $this->assertCount(6, $returnArray);

        $expected = array(
            'Passive checks' => false,
            'Active checks' => true,
            'Obsessing' => false,
            'Notifications' => true,
            'Event handler' => true,
            'Flap detection' => false
        );

        $this->assertEquals($expected, $returnArray);
    }

    public function testUglyConditions1()
    {
        $testArray = array(
            'service_active_checks_enabled' => '1',
            'service_obsess_over_host' => '1',
            'DING DING' => '$$$',
            'DONG DONG' => '###'
        );

        $monitoringFlags = new \Zend_View_Helper_MonitoringFlags();
        $returnArray = $monitoringFlags->monitoringFlags((object)$testArray);

        $this->assertCount(6, $returnArray);

        $expected = array(
            'Passive checks' => false,
            'Active checks' => true,
            'Obsessing' => true,
            'Notifications' => false,
            'Event handler' => false,
            'Flap detection' => false
        );

        $this->assertEquals($expected, $returnArray);
    }

    public function testUglyConditions2()
    {
        $testArray = array(
            'DING DING' => '$$$',
            'DONG DONG' => '###'
        );

        $monitoringFlags = new \Zend_View_Helper_MonitoringFlags();
        $returnArray = $monitoringFlags->monitoringFlags((object)$testArray);

        $this->assertCount(6, $returnArray);

        $expected = array(
            'Passive checks' => false,
            'Active checks' => false,
            'Obsessing' => false,
            'Notifications' => false,
            'Event handler' => false,
            'Flap detection' => false
        );

        $this->assertEquals($expected, $returnArray);
    }
}