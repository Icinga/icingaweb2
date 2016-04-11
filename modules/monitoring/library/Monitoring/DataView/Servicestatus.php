<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class ServiceStatus extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array_merge($this->getHookedColumns(), array(
            'host_acknowledged',
            'host_action_url',
            'host_active_checks_enabled',
            'host_address',
            'host_address6',
            'host_alias',
            'host_check_source',
            'host_display_name',
            'host_handled',
            'host_hard_state',
            'host_in_downtime',
            'host_ipv4',
            'host_is_flapping',
            'host_last_check',
            'host_last_hard_state',
            'host_last_hard_state_change',
            'host_last_state_change',
            'host_last_time_down',
            'host_last_time_unreachable',
            'host_last_time_up',
            'host_long_output',
            'host_modified_host_attributes',
            'host_name',
            'host_notes_url',
            'host_notifications_enabled',
            'host_output',
            'host_passive_checks_enabled',
            'host_perfdata',
            'host_problem',
            'host_severity',
            'host_state',
            'host_state_type',
            'host_unhandled_service_count',
            'instance_name',
            'service_acknowledged',
            'service_acknowledgement_type',
            'service_action_url',
            'service_active_checks_enabled',
            'service_active_checks_enabled_changed',
            'service_attempt',
            'service_check_command',
            'service_check_source',
            'service_check_timeperiod',
            'service_current_check_attempt',
            'service_current_notification_number',
            'service_description',
            'service_display_name',
            'service_event_handler_enabled',
            'service_event_handler_enabled_changed',
            'service_flap_detection_enabled',
            'service_flap_detection_enabled_changed',
            'service_handled',
            'service_hard_state',
            'service_host_name',
            'service_in_downtime',
            'service_is_flapping',
            'service_is_reachable',
            'service_last_check',
            'service_last_hard_state',
            'service_last_hard_state_change',
            'service_last_notification',
            'service_last_state_change',
            'service_last_time_critical',
            'service_last_time_ok',
            'service_last_time_unknown',
            'service_last_time_warning',
            'service_long_output',
            'service_max_check_attempts',
            'service_modified_service_attributes',
            'service_next_check',
            'service_notes',
            'service_notes_url',
            'service_notifications_enabled',
            'service_notifications_enabled_changed',
            'service_obsessing',
            'service_obsessing_changed',
            'service_output',
            'service_passive_checks_enabled',
            'service_passive_checks_enabled_changed',
            'service_perfdata',
            'service_problem',
            'service_severity',
            'service_state',
            'service_state_type',
            'service_unhandled'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getSortRules()
    {
        return array(
            'service_display_name' => array(
                'columns' => array(
                    'service_display_name',
                    'host_display_name'
                ),
                'order' => self::SORT_ASC
            ),
            'service_severity' => array(
                'columns' => array(
                    'service_severity',
                    'service_last_state_change DESC',
                    'service_display_name ASC',
                    'host_display_name ASC'
                ),
                'order' => self::SORT_DESC
            ),
            'service_last_state_change' => array(
                'order' => self::SORT_DESC
            ),
            'host_severity' => array(
                'columns' => array(
                    'host_severity',
                    'host_last_state_change DESC',
                    'host_display_name ASC',
                    'service_display_name ASC'
                ),
                'order' => self::SORT_DESC
            ),
            'host_display_name' => array(
                'columns' => array(
                    'host_display_name',
                    'service_display_name'
                ),
                'order' => self::SORT_ASC
            ),
            'host_address' => array(
                'columns' => array(
                    'host_ipv4',
                    'service_display_name'
                ),
                'order' => self::SORT_ASC
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticFilterColumns()
    {
        return array(
            'host',
            'hostgroup',
            'hostgroup_alias',
            'hostgroup_name',
            'service',
            'service_host',
            'servicegroup',
            'servicegroup_alias',
            'servicegroup_name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchColumns()
    {
        return array('service', 'service_display_name');
    }
}
