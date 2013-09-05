<?php

namespace Test\Modules\Monitoring\Application\Views\Helpers;

require_once 'Zend/View/Helper/Abstract.php';
require_once 'Zend/View.php';
require_once __DIR__. '/../../../../../application/views/helpers/MonitoringProperties.php';

/**
  * @TODO(el): This test is subject to bug #4679 and 
  */
class HostStruct4Properties extends \stdClass
{
    public $host_name = 'localhost';
    public $host_address = '127.0.0.1';
    public $host_state = '1';
    public $host_handled = '1';
    public $host_in_downtime = '1';
    public $host_acknowledged = '1';
    public $host_check_command = 'check-host-alive';
    public $host_last_state_change = '1372937083';
    public $host_alias = 'localhost';
    public $host_output = 'DDD';
    public $host_long_output = '';
    public $host_perfdata = '';
    public $host_current_check_attempt = '1';
    public $host_max_check_attempts = '10';
    public $host_attempt = '1/10';
    public $host_last_check = '2013-07-04 11:24:42';
    public $host_next_check = '2013-07-04 11:29:43';
    public $host_check_type = '1';
    public $host_last_hard_state_change = '2013-07-04 11:24:43';
    public $host_last_hard_state = '0';
    public $host_last_time_up = '2013-07-04 11:20:23';
    public $host_last_time_down = '2013-07-04 11:24:43';
    public $host_last_time_unreachable = '0000-00-00 00:00:00';
    public $host_state_type = '1';
    public $host_last_notification = '0000-00-00 00:00:00';
    public $host_next_notification = '0000-00-00 00:00:00';
    public $host_no_more_notifications = '0';
    public $host_notifications_enabled = '1';
    public $host_problem_has_been_acknowledged = '1';
    public $host_acknowledgement_type = '2';
    public $host_current_notification_number = '0';
    public $host_passive_checks_enabled = '1';
    public $host_active_checks_enabled = '0';
    public $host_event_handler_enabled = '0';
    public $host_flap_detection_enabled = '1';
    public $host_is_flapping = '0';
    public $host_percent_state_change = '12.36842';
    public $host_check_latency = '0.12041';
    public $host_check_execution_time = '0';
    public $host_scheduled_downtime_depth = '1';
    public $host_failure_prediction_enabled = '1';
    public $host_process_performance_data = '1';
    public $host_obsessing = '1';
    public $host_modified_host_attributes = '14';
    public $host_event_handler = '';
    public $host_normal_check_interval = '5';
    public $host_retry_check_interval = '1';
    public $host_check_timeperiod_object_id = '27';
    public $host_status_update_time = '2013-07-08 10:10:10';
}

class MonitoringPropertiesTest extends \PHPUnit_Framework_TestCase
{
    public function testOutput1()
    {
        $host = new HostStruct4Properties();
        $host->host_current_check_attempt = '5';

        $propertyHelper = new \Zend_View_Helper_MonitoringProperties();
        $items = $propertyHelper->monitoringProperties($host);

        $this->assertCount(10, $items);
        $this->assertEquals('5/10 (HARD state)', $items['Current Attempt']);
        $this->assertEquals('2013-07-08 10:10:10', $items['Last Update']);
    }

    public function testOutput2()
    {
        date_default_timezone_set("UTC");
        $host = new HostStruct4Properties();
        $host->host_current_check_attempt = '5';
        $host->host_active_checks_enabled = '1';
        $host->host_passive_checks_enabled = '0';
        $host->host_is_flapping = '1';

        $propertyHelper = new \Zend_View_Helper_MonitoringProperties();
        $items = $propertyHelper->monitoringProperties($host);

        $this->assertCount(10, $items);

        $test = array(
            'Current Attempt' => "5/10 (HARD state)",
            'Last Check Time' => "2013-07-04 11:24:42",
            'Check Type' => "ACTIVE",
            'Check Latency / Duration' => "0.1204 / 0.0000 seconds",
            'Next Scheduled Active Check' => "2013-07-04 11:29:43",
            'Last State Change' => "2013-07-04 11:24:43",
            'Last Notification' => "N/A (notification 0)",
            'Is This Host Flapping?' => "YES (12.37% state change)",
            'In Scheduled Downtime?' => "YES",
            'Last Update' => "2013-07-08 10:10:10",
        );

        $this->assertEquals($test, $items);
    }
}
