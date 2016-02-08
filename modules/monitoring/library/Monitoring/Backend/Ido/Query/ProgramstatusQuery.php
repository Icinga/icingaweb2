<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Program status query
 */
class ProgramstatusQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'programstatus' => array(
            'id'                    => 'programstatus_id',
            'status_update_time'    => 'UNIX_TIMESTAMP(programstatus.status_update_time)',
            'program_version'       => 'program_version',
            'program_start_time'    => 'UNIX_TIMESTAMP(programstatus.program_start_time)',
            'program_end_time'      => 'UNIX_TIMESTAMP(programstatus.program_end_time)',
            'is_currently_running'  => 'CASE WHEN (UNIX_TIMESTAMP(programstatus.status_update_time) + 60 > UNIX_TIMESTAMP(NOW()))
                THEN
                    1
                ELSE
                    0
                END',
            'process_id'                        => 'process_id',
            'endpoint_name'                     => 'endpoint_name',
            'daemon_mode'                       => 'daemon_mode',
            'last_command_check'                => 'UNIX_TIMESTAMP(programstatus.last_command_check)',
            'last_log_rotation'                 => 'UNIX_TIMESTAMP(programstatus.last_log_rotation)',
            'notifications_enabled'             => 'notifications_enabled',
            'disable_notif_expire_time'         => 'UNIX_TIMESTAMP(programstatus.disable_notif_expire_time)',
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

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        parent::joinBaseTables();

        if (version_compare($this->getIdoVersion(), '1.11.7', '<')) {
            $this->columnMap['programstatus']['endpoint_name'] = '(0)';
        }
        if (version_compare($this->getIdoVersion(), '1.11.8', '<')) {
            $this->columnMap['programstatus']['program_version'] = '(NULL)';
        }
        if (version_compare($this->getIdoVersion(), '1.8', '<')) {
            $this->columnMap['programstatus']['disable_notif_expire_time'] = '(NULL)';
        }
    }
}
