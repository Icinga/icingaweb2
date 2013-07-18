<?php


namespace Test\Monitoring\Testlib\DataSource\schemes;

/**
 * Container class for Objectcache object snipptes
 *
 */
class ObjectsCacheTemplates {

    public static $HOST =<<<'EOF'
    define host {
        host_name\t{{HOST_NAME}}
        alias\t{{HOST_NAME}} alias
        address\t{{HOST_ADDRESS}}
        check_period\t24x7
        check_command\ttest-check-host-alive-parent!flap!$HOSTSTATE:router_00$
        contact_groups\ttest_contact
        notification_period\t24x7
        initial_state\to
        check_interval\t10.000000
        retry_interval\t1.000000
        max_check_attempts\t5
        active_checks_enabled\t1
        passive_checks_enabled\t1
        obsess_over_host\t1
        event_handler_enabled\t1
        low_flap_threshold\t0.000000
        high_flap_threshold\t0.000000
        flap_detection_enabled\t1
        flap_detection_options\to,d,u
        freshness_threshold\t0
        check_freshness\t0
        notification_options\td,u,r
        notifications_enabled\t1
        notification_interval\t0.000000
        first_notification_delay\t0.000000
        stalking_options\tn
        process_perf_data\t1
        failure_prediction_enabled\t1
        icon_image\t{{ICON_IMAGE}}
        icon_image_alt\ticon alt string
        notes\tjust a notes string
        notes_url\t{{NOTES_URL}}
        action_url\t{{ACTION_URL}}
        retain_status_information\t1
        retain_nonstatus_information\t1
    }
EOF;
    public static $SERVICE =<<<'EOF'
    define service {
        host_name\t{{HOST_NAME}}
        service_description\t{{SERVICE_NAME}}
        check_period\t24x7
        check_command\tcheck_service!critical
        contact_groups\ttest_contact
        notification_period\t24x7
        initial_state\to
        check_interval\t5.000000
        retry_interval\t2.000000
        max_check_attempts\t3
        is_volatile\t0
        parallelize_check\t1
        active_checks_enabled\t1
        passive_checks_enabled\t1
        obsess_over_service\t1
        event_handler_enabled\t1
        low_flap_threshold\t0.000000
        high_flap_threshold\t0.000000
        flap_detection_enabled\t1
        flap_detection_options\to,w,u,c
        freshness_threshold\t0
        check_freshness\t0
        notification_options\tu,w,c,r
        notifications_enabled\t1
        notification_interval\t0.000000
        first_notification_delay\t0.000000
        stalking_options\tn
        process_perf_data\t1
        failure_prediction_enabled\t1
        icon_image\t{{ICON_IMAGE}}
        icon_image_alt\ticon alt string
        notes\tjust a notes string
        notes_url\t{{NOTES_URL}}
        action_url\t{{ACTION_URL}}
        retain_status_information\t1
        retain_nonstatus_information\t1
    }
EOF;

    public static $GROUP =<<<'EOF'
    define {{TYPE}}group {
        {{TYPE}}group_name\t{{NAME}}
        alias\t{{NAME}}
        members\t{{MEMBERS}}
    }
EOF;

};