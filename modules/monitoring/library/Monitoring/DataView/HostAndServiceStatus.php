<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\DataView;

class HostAndServiceStatus extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'host_name',
            'host_state',
            'host_state_type',
            'host_last_state_change',
            'host_address',
            'host_handled',
            'service_description',
            'service_display_name',
            'service_state',
            'service_in_downtime',
            'service_acknowledged',
            'service_handled',
            'service_output',
            'service_last_state_change',
            'service_icon_image',
            'service_long_output',
            'service_is_flapping',
            'service_state_type',
            'service_severity',
            'service_last_check',
            'service_notifications_enabled',
            'service_action_url',
            'service_notes_url',
            'service_last_comment',
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
            'host',
            'host_display_name',
            'host_alias',
            'host_ipv4',
//            'host_problems',
            'host_severity',
            'host_perfdata',
            'host_does_active_checks',
            'host_accepts_passive_checks',
            'host_last_hard_state',
            'host_last_hard_state_change',
            'host_last_time_up',
            'host_last_time_down',
            'host_last_time_unreachable',
            'service',
//            'current_state',
            'service_hard_state',
            'service_perfdata',
            'service_does_active_checks',
            'service_accepts_passive_checks',
            'service_last_hard_state',
            'service_last_hard_state_change',
            'service_last_time_ok',
            'service_last_time_warning',
            'service_last_time_critical',
            'service_last_time_unknown',
            'service_current_check_attempt',
            'service_max_check_attempts'
//            'object_type',
//            'problems',
//            'handled',
//            'severity'
        );
    }

    public static function getTableName()
    {
        return 'status';
    }

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

    protected function isValidFilterColumn($column)
    {
        if ($column[0] === '_'
            && preg_match('/^_(?:host|service)_/', $column)
        ) {
            return true;
        }
        return parent::isValidFilterColumn($column);
    }
}
