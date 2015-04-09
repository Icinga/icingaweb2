<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class HostStatus extends DataView
{
    /**
     * @see DataView::init()
     */
    public function init()
    {
        $this->query->setMode('host');
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
            'host_alias',
            'host_address',
            'host_state',
            'host_state_type',
            'host_handled',
            'host_unhandled',
            'host_in_downtime',
            'host_acknowledged',
            'host_last_state_change',
            'host_last_state_change',
            'host_last_notification',
            'host_last_check',
            'host_next_check',
            'host_check_execution_time',
            'host_check_latency',
            'host_output',
            'host_long_output',
            'host_check_command',
            'host_perfdata',
            'host_check_source',
            'host_unhandled_services',
            'host_passive_checks_enabled',
            'host_passive_checks_enabled_changed',
            'host_obsessing',
            'host_obsessing_changed',
            'host_notifications_enabled',
            'host_notifications_enabled_changed',
            'host_event_handler_enabled',
            'host_event_handler_enabled_changed',
            'host_flap_detection_enabled',
            'host_flap_detection_enabled_changed',
            'host_active_checks_enabled',
            'host_active_checks_enabled_changed',
            'host_current_check_attempt',
            'host_max_check_attempts',
            'host_last_notification',
            'host_current_notification_number',
            'host_percent_state_change',
            'host_is_flapping',
            'host_last_comment',
            'host_last_downtime',
            'host_last_ack',
            'host_last_flapping',
            'host_action_url',
            'host_notes_url',
            'host_percent_state_change',
            'host_modified_host_attributes',
            'host_severity',
            'host_problem',
            'host_ipv4'
        );
    }

    public static function getQueryName()
    {
        return 'status';
    }

    /**
     * The sort rules for this query
     *
     * @return array
     */
    public function getSortRules()
    {
        return array(
            'host_display_name' => array(
                'order' => self::SORT_ASC
            ),
            'host_severity' => array(
                'columns' => array(
                    'host_severity DESC',
                    'host_last_state_change DESC',
                    'host_display_name ASC'
                )
            ),
            'host_address' => array(
                'columns' => array(
                    'host_ipv4'
                ),
                'order' => self::SORT_ASC
            ),
            'host_last_state_change' => array(
                'order' => self::SORT_ASC
            )
        );
    }

    public function getFilterColumns()
    {
        return array('host', 'hostgroup', 'hostgroup_name', 'service', 'servicegroup', 'servicegroup_name');
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
