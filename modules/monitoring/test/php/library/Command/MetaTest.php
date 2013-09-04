<?php

namespace Test\Modules\Monitoring\Library\Command;

require_once __DIR__. '/../../../../library/Monitoring/Command/Meta.php';
require_once __DIR__. '/../../../../../../library/Icinga/Exception/ProgrammingError.php';

use \Icinga\Module\Monitoring\Command\Meta;

class HostStruct extends \stdClass
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
    public $host_latency = '0.12041';
    public $host_execution_time = '0';
    public $host_scheduled_downtime_depth = '1';
    public $host_failure_prediction_enabled = '1';
    public $host_process_performance_data = '1';
    public $host_obsessing = '1';
    public $host_modified_host_attributes = '14';
    public $host_event_handler = '';
    public $host_normal_check_interval = '5';
    public $host_retry_check_interval = '1';
    public $host_check_timeperiod_object_id = '27';
}

class MetaTest extends \PHPUnit_Framework_TestCase
{
    public function testRawCommands1()
    {
        $meta = new Meta();
        $commands = $meta->getRawCommands();

        $this->assertCount(173, $commands);

        $this->assertTrue(in_array('SCHEDULE_SERVICEGROUP_SVC_DOWNTIME', $commands));
        $this->assertTrue(in_array('SCHEDULE_SVC_CHECK', $commands));
        $this->assertTrue(in_array('ENABLE_HOSTGROUP_SVC_CHECKS', $commands));
        $this->assertTrue(in_array('PROCESS_HOST_CHECK_RESULT', $commands));
        $this->assertTrue(in_array('ACKNOWLEDGE_SVC_PROBLEM', $commands));
        $this->assertTrue(in_array('ACKNOWLEDGE_HOST_PROBLEM', $commands));
        $this->assertTrue(in_array('SCHEDULE_FORCED_SVC_CHECK', $commands));
        $this->assertTrue(in_array('DISABLE_FLAP_DETECTION', $commands));
    }

    public function testRawCommands2()
    {
        $meta = new Meta();
        $categories = $meta->getRawCategories();

        $this->assertCount(7, $categories);

        $this->assertEquals(
            array(
                'comment',
                'contact',
                'global',
                'host',
                'hostgroup',
                'service',
                'servicegroup'
            ),
            $categories
        );
    }

    public function testRawCommands3()
    {
        $meta = new Meta();

        $this->assertCount(9, $meta->getRawCommandsForCategory('hostgroup'));
        $this->assertCount(14, $meta->getRawCommandsForCategory('servicegroup'));

        $test1 = $meta->getRawCommandsForCategory('global');
        $this->count(26, $test1);

        $this->assertTrue(in_array('DISABLE_NOTIFICATIONS', $test1));
        $this->assertTrue(in_array('RESTART_PROCESS', $test1));
        $this->assertTrue(in_array('ENABLE_FLAP_DETECTION', $test1));
        $this->assertTrue(in_array('PROCESS_FILE', $test1));
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage Category does not exists: DOES_NOT_EXIST
     */
    public function testRawCommands4()
    {
        $meta = new Meta();
        $meta->getRawCommandsForCategory('DOES_NOT_EXIST');
    }

    public function testObjectForCommand1()
    {
        $meta = new Meta();

        $object = new HostStruct();

        $commands = $meta->getCommandForObject($object, Meta::TYPE_SMALL);

        $this->assertEquals(3, $commands[0]->id);
        $this->assertEquals(27, $commands[1]->id);

        $object->host_state = '0';

        $commands = $meta->getCommandForObject($object, Meta::TYPE_SMALL);

        $this->assertEquals(3, $commands[0]->id);
        $this->assertFalse(isset($commands[1])); // STATE IS OK AGAIN

        $object->host_state = '1';
        $object->host_acknowledged = '0';

        $commands = $meta->getCommandForObject($object, Meta::TYPE_SMALL);

        $this->assertEquals(3, $commands[0]->id);
        $this->assertEquals(26, $commands[1]->id);
    }

    public function testObjectForCommand2()
    {
        $meta = new Meta();

        $object = new HostStruct();

        $object->host_obsessing = '0';
        $object->host_flap_detection_enabled = '0';
        $object->host_active_checks_enabled = '0';

        $commands = $meta->getCommandForObject($object, Meta::TYPE_FULL);

        $this->assertEquals(2, $commands[0]->id);
        $this->assertEquals(6, $commands[3]->id);
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     * @expectedExceptionMessage Type has no commands defined: UNKNOWN
     */
    public function testObjectForCommand3()
    {
        $meta = new Meta();

        $test = new \stdClass();
        $test->UNKNOWN_state = '2';
        $test->UNKNOWN_flap_detection_enabled = '1';

        $commands = $meta->getCommandForObject($test, Meta::TYPE_FULL);
    }
}
