<?php

namespace Icinga\Module\Monitoring\View;

class StatusView extends MonitoringView
{
    protected $query;
    // protected $searchColumn = 'host'; -> besser in der Query, 'search' mitgeben

    protected $availableColumns = array(
        // Hosts
        'host',
        'host_name',
        'host_display_name',
        'host_alias',
        'host_address',
        'host_ipv4',
        'host_icon_image',

        // Hoststatus
        'host_state',
        'host_problems',
        'host_severity',
        'host_state_type',
        'host_output',
        'host_long_output',
        'host_perfdata',
        'host_acknowledged',
        'host_in_downtime',
        'host_handled',
        'host_does_active_checks',
        'host_accepts_passive_checks',
        'host_last_state_change',
        'host_last_hard_state',
        'host_last_hard_state_change',
        'host_notifications_enabled',
        'host_last_time_up',
        'host_last_time_down',
        'host_last_time_unreachable',
        'host_unhandled_service_count',

        // Services
        'service',
        'service_description',
        'service_display_name',

        // Servicetatus
        'current_state',
        'service_state',
        'service_state_type',
        'service_hard_state',
        'service_output',
        'service_long_output',
        'service_perfdata',
        'service_acknowledged',
        'service_in_downtime',
        'service_handled',
        'service_does_active_checks',
        'service_accepts_passive_checks',
        'service_last_state_change',
        'service_last_hard_state',
        'service_last_hard_state_change',
        'service_notifications_enabled',
        'service_last_time_ok',
        'service_last_time_warning',
        'service_last_time_critical',
        'service_last_time_unknown',

        'object_type',

        // Status
        'problems',
        'handled',
        'severity'
    );

    protected $specialFilters = array(
        'hostgroups',
        'servicegroups'
    );

    protected $sortDefaults = array(
        'host_name' => array(
            'columns' => array(
                'host_name',
            ),
            'default_dir' => self::SORT_ASC
        ),
        'service_host_name' => array(
            'columns' => array(
                'service_host_name',
                'service_description'
            ),
            'default_dir' => self::SORT_ASC
        ),
        'host_address' => array(
            'columns' => array(
                'host_ipv4',
                'service_description'
             ),
             'default_dir' => self::SORT_ASC
        ),
        'host_last_state_change' => array(
            'default_dir' => self::SORT_DESC
        ),
        'service_last_state_change' => array(
            'default_dir' => self::SORT_DESC
        ),
        'severity' => array(
            'columns' => array(
                'severity',
                'host_name',
                'service_last_state_change',
            ),
            'default_dir' => self::SORT_DESC
        ),
        'host_severity' => array(
            'columns' => array(
                'host_severity',
                'host_last_state_change',
            ),
            'default_dir' => self::SORT_DESC
        )
    );

    public function isValidFilterColumn($column)
    {
        if ($column[0] === '_'
            && preg_match('~^_(?:host|service)_~', $column)
        ) {
            return true;
        }
		return parent::isValidFilterColumn($column);
    }
}
