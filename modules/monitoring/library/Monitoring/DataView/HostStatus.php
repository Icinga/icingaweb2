<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\DataView;

use Icinga\Module\Monitoring\Filter\MonitoringFilter;

class HostStatus extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'host',
            'host_name',
            'host_alias',
            'host_address',
            'host_state',
            'host_state_type',
            'host_handled',
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
            'host_passive_checks_enabled',
            'host_obsessing',
            'host_notifications_enabled',
            'host_event_handler_enabled',
            'host_flap_detection_enabled',
            'host_active_checks_enabled',
            'host_current_check_attempt',
            'host_max_check_attempts',
            'host_last_notification',
            'host_current_notification_number',
            'host_percent_state_change',
            'host_is_flapping',
            'host_last_comment',
            'host_action_url',
            'host_notes_url',
            'host_percent_state_change'
        );
    }

    /**
     * Return the table name
     *
     * @return string
     */
    public static function getTableName()
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
            'host_name' => array(
                'order' => self::SORT_ASC
            ),
            'host_address' => array(
                'columns' => array(
                    'host_ipv4',
                    'service_description'
                ),
                'order' => self::SORT_ASC
            ),
            'host_last_state_change' => array(
                'order' => self::SORT_ASC
            ),
            'host_severity' => array(
                'columns' => array(
                    'host_severity',
                    'host_last_state_change',
                ),
                'order' => self::SORT_ASC
            )
        );
    }

    public function getFilterColumns()
    {
        return array('hostgroups', 'servicegroups', 'service_problems');
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
