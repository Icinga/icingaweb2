<?php

namespace Icinga\Module\Monitoring\View;

class HoststatusView extends MonitoringView
{
    protected $query;
    protected $searchColumn = 'host';

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
        'host_problem',
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
            && preg_match('~^_host~', $column)
        ) {
            return true;
        }
		return parent::isValidFilterColumn($column);
    }
}
