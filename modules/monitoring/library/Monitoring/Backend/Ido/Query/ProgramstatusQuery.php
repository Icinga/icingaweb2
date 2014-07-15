<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query program status out of database
 */
class ProgramstatusQuery extends IdoQuery
{
    protected $columnMap = array(
        'programstatus' => array(
            'id'                                => 'programstatus_id',
            'status_update_time'                => 'UNIX_TIMESTAMP(status_update_time)',
            'program_start_time'                => 'UNIX_TIMESTAMP(program_start_time)',
            'program_end_time'                  => 'UNIX_TIMESTAMP(program_end_time)',
            'is_currently_running'              => 'is_currently_running',
            'process_id'                        => 'process_id',
            'daemon_mode'                       => 'daemon_mode',
            'last_command_check'                => 'UNIX_TIMESTAMP(last_command_check)',
            'last_log_rotation'                 => 'UNIX_TIMESTAMP(last_log_rotation)',
            'notifications_enabled'             => 'notifications_enabled',
            'disable_notif_expire_time'         => 'UNIX_TIMESTAMP(disable_notif_expire_time)',
            'active_service_checks_enabled'     => 'active_service_checks_enabled',
            'passive_service_checks_enabled'    => 'passive_service_checks_enabled',
            'active_host_checks_enabled'        => 'active_host_checks_enabled',
            'passive_host_checks_enabled'       => 'passive_host_checks_enabled',
            'event_handlers_enabled'            => 'event_handlers_enabled',
            'flap_detection_enabled'            => 'flap_detection_enabled',
            'failure_prediction_enabled'        => 'failure_prediction_enabled',
            'process_performance_data'          => 'process_performance_data',
            'obsess_over_hosts'                 => 'obsess_over_hosts',
            'obsess_over_services'              => 'obsess_over_services',
            'modified_host_attributes'          => 'modified_host_attributes',
            'modified_service_attributes'       => 'modified_service_attributes',
            'global_host_event_handler'         => 'global_host_event_handler',
            'global_service_event_handler'      => 'global_service_event_handler',
        )
    );
}
