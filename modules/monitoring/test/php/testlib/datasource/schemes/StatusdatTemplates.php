<?php
/**
 * Created by JetBrains PhpStorm.
 * User: moja
 * Date: 7/17/13
 * Time: 10:25 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Test\Monitoring\Testlib\DataSource\schemes;

/**
 * Container class for Statusdat object snippets
 *
 */
class StatusdatTemplates {

    public static $HOST =<<<'EOF'
    hoststatus {
        host_name={{HOST_NAME}}
        modified_attributes=0
        check_command=test-check-host-alive-parent!pending!$HOSTSTATE:router_01$
        check_period=24x7
        notification_period=24x7
        check_interval=10.000000
        retry_interval=1.000000
        event_handler=
        has_been_checked=0
        should_be_scheduled=0
        check_execution_time=0.000
        check_latency=0.000
        check_type=0
        current_state={{HOST_STATUS}}
        last_hard_state=0
        last_event_id=14750
        current_event_id=14756
        current_problem_id=6016
        last_problem_id=6010
        plugin_output=Plugin output for host {{HOST_NAME}}
        long_plugin_output=Long plugin output for host {{HOST_NAME}}
        performance_data=
        last_check={{TIME}}
        next_check=1374002661
        check_options=0
        current_attempt=2
        max_attempts=5
        state_type=0
        last_state_change={{TIME}}
        last_hard_state_change={{TIME}}
        last_time_up=1373984768
        last_time_down=1373984818
        last_time_unreachable=1373984748
        last_notification=0
        next_notification=0
        no_more_notifications=1
        current_notification_number=0
        current_down_notification_number=0
        current_unreachable_notification_number=0
        current_notification_id=0
        notifications_enabled={{NOTIFICATIONS_ENABLED}}
        problem_has_been_acknowledged={{ACKNOWLEDGED}}
        acknowledgement_type=0
        acknowledgement_end_time=0
        active_checks_enabled={{ACTIVE_ENABLED}}
        passive_checks_enabled={{PASSIVE_ENABLED}}
        event_handler_enabled=1
        flap_detection_enabled=1
        failure_prediction_enabled=1
        process_performance_data=1
        obsess_over_host=1
        last_update=1374002209
        is_flapping={{FLAPPING}}
        percent_state_change=0.00
        scheduled_downtime_depth={{IN_DOWNTIME}}
        {{CVS}}
    }
EOF;

    public static $SERIVCE =<<<'EOF'
    servicestatus {
        host_name={{HOST_NAME}}
        service_description={{SERVICE_NAME}}
        modified_attributes=0
        check_command=check_service!critical
        check_period=24x7
        notification_period=24x7
        check_interval=5.000000
        retry_interval=2.000000
        event_handler=
        has_been_checked=1
        should_be_scheduled=1
        check_execution_time=0.250
        check_latency=0.113
        check_type=0
        current_state={{SERVICE_STATUS}}
        last_hard_state=2
        last_event_id=0
        current_event_id=6179
        current_problem_id=2434
        last_problem_id=0
        current_attempt=3
        max_attempts=3
        state_type=1
        last_state_change={{TIME}}
        last_hard_state_change={{TIME}}
        last_time_ok=0
        last_time_warning=0
        last_time_unknown=0
        last_time_critical=1373024663
        plugin_output=Plugin output for service {{SERVICE_NAME}}
        long_plugin_output=Long plugin output for service {{SERVICE_NAME}}
        performance_data=runtime=0.012226
        last_check=1373087666
        next_check=1374002401
        check_options=0
        current_notification_number=0
        current_warning_notification_number=0
        current_critical_notification_number=0
        current_unknown_notification_number=0
        current_notification_id=0
        last_notification=0
        next_notification=0
        no_more_notifications=1
        notifications_enabled={{NOTIFICATIONS_ENABLED}}
        active_checks_enabled={{ACTIVE_ENABLED}}
        passive_checks_enabled={{PASSIVE_ENABLED}}
        event_handler_enabled=1
        problem_has_been_acknowledged={{ACKNOWLEDGED}}
        acknowledgement_type=1
        acknowledgement_end_time=0
        flap_detection_enabled=1
        failure_prediction_enabled=1
        process_performance_data=1
        obsess_over_service=1
        last_update=1374002209
        is_flapping={{FLAPPING}}
        percent_state_change=6.25
        scheduled_downtime_depth={{IN_DOWNTIME}}
        {{CVS}}
        }
EOF;

    public static $SERVICECOMMENT =<<<'EOF'
    servicecomment {
        host_name={{HOST_NAME}}
        service_description={{SERVICE_NAME}}
        entry_type=3
        comment_id={{ID}}
        source=0
        persistent=0
        entry_time={{TIME}}
        expires=0
        expire_time=0
        author={{AUTHOR}}
        comment_data={{TEXT}
    }
EOF;

    public static $HOSTCOMMENT =<<<'EOF'
    hostcomment {
        host_name={{HOST_NAME}}
        entry_type=3
        comment_id={{ID}}
        source=0
        persistent=0
        entry_time={{TIME}}
        expires=0
        expire_time=0
        author={{AUTHOR}}
        comment_data={{TEXT}}
    }
EOF;
}
