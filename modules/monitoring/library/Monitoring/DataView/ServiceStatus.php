<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class ServiceStatus extends DataView
{
    /**
     * Sets the mode for `distinct as workaround
     *
     * @TODO Subject to change, see #7344
     */
    public function init()
    {
        $this->query->setMode('service');
    }

    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'host_name',
            'host_display_name',
            'host_state',
            'host_state_type',
            'host_last_state_change',
            'host_address',
            'host_problem',
            'host_handled',
            'service_description',
            'service_display_name',
            'service_state',
            'service_in_downtime',
            'service_acknowledged',
            'service_handled',
            'service_unhandled',
            'service_output',
            'service_last_state_change',
            'service_icon_image',
            'service_long_output',
            'service_is_flapping',
            'service_state_type',
            'service_severity',
            'service_last_check',
            'service_notifications_enabled',
            'service_notifications_enabled_changed',
            'service_action_url',
            'service_notes_url',
            'service_last_comment',
            'service_last_downtime',
            'service_last_flapping',
            'service_last_ack',
            'service_last_check',
            'service_next_check',
            'service_attempt',
            'service_last_notification',
            'service_check_command',
            'service_current_notification_number',
            'host_icon_image',
            'host_acknowledged',
            'host_output',
            'host_long_output',
            'host_in_downtime',
            'host_is_flapping',
            'host_last_check',
            'host_notifications_enabled',
            'host_unhandled_service_count',
            'host_action_url',
            'host_notes_url',
            'host_last_comment',
            'host_display_name',
            'host_alias',
            'host_ipv4',
            'host_severity',
            'host_perfdata',
            'host_check_source',
            'host_active_checks_enabled',
            'host_passive_checks_enabled',
            'host_last_hard_state',
            'host_last_hard_state_change',
            'host_last_time_up',
            'host_last_time_down',
            'host_last_time_unreachable',
            'host_modified_host_attributes',
            'service_hard_state',
            'service_problem',
            'service_perfdata',
            'service_check_source',
            'service_active_checks_enabled',
            'service_active_checks_enabled_changed',
            'service_passive_checks_enabled',
            'service_passive_checks_enabled_changed',
            'service_last_hard_state',
            'service_last_hard_state_change',
            'service_last_time_ok',
            'service_last_time_warning',
            'service_last_time_critical',
            'service_last_time_unknown',
            'service_current_check_attempt',
            'service_max_check_attempts',
            'service_obsessing',
            'service_obsessing_changed',
            'service_event_handler_enabled',
            'service_event_handler_enabled_changed',
            'service_flap_detection_enabled',
            'service_flap_detection_enabled_changed',
            'service_modified_service_attributes',
            'service_host_name'
        );
    }

    public static function getQueryName()
    {
        return 'status';
    }

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
                    'service_severity DESC',
                    'service_last_state_change DESC',
                    'service_display_name ASC',
                    'host_display_name ASC'
                )
            ),
            'host_severity' => array(
                'columns' => array(
                    'host_severity DESC',
                    'host_last_state_change DESC',
                    'host_display_name ASC',
                    'service_display_name ASC'
                )
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

    public function getFilterColumns()
    {
        return array(
            'host',
            'hostgroup',
            'hostgroup_name',
            'service',
            'service_host',
            'servicegroup',
            'servicegroup_name'
        );
    }

    public function isValidFilterTarget($column)
    {
        if ($column[0] === '_'
            && preg_match('/^_(?:host|service)_/', $column)
        ) {
            return true;
        }
        return parent::isValidFilterTarget($column);
    }
}
