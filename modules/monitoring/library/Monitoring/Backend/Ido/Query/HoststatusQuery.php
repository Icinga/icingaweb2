<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;

class HoststatusQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'hosts' => array(
            'host'                  => 'ho.name1 COLLATE latin1_general_ci',
            'host_action_url'       => 'h.action_url',
            'host_address'          => 'h.address',
            'host_alias'            => 'h.alias',
            'host_display_name'     => 'h.display_name COLLATE latin1_general_ci',
            'host_icon_image'       => 'h.icon_image',
            'host_icon_image_alt'   => 'h.icon_image_alt',
            'host_ipv4'             => 'INET_ATON(h.address)',
            'host_name'             => 'ho.name1',
            'host_notes_url'        => 'h.notes_url',
            'object_type'           => '(\'host\')'
        ),
        'hoststatus' => array(
            'host_acknowledged'                     => 'hs.problem_has_been_acknowledged',
            'host_acknowledgement_type'             => 'hs.acknowledgement_type',
            'host_active_checks_enabled'            => 'hs.active_checks_enabled',
            'host_active_checks_enabled_changed'    => 'CASE WHEN hs.active_checks_enabled = h.active_checks_enabled THEN 0 ELSE 1 END',
            'host_attempt'                          => 'hs.current_check_attempt || \'/\' || hs.max_check_attempts',
            'host_check_command'                    => 'hs.check_command',
            'host_check_execution_time'             => 'hs.execution_time',
            'host_check_latency'                    => 'hs.latency',
            'host_check_source'                     => 'hs.check_source',
            'host_check_type'                       => 'hs.check_type',
            'host_current_check_attempt'            => 'hs.current_check_attempt',
            'host_current_notification_number'      => 'hs.current_notification_number',
            'host_event_handler'                    => 'hs.event_handler',
            'host_event_handler_enabled'            => 'hs.event_handler_enabled',
            'host_event_handler_enabled_changed'    => 'CASE WHEN hs.event_handler_enabled = h.event_handler_enabled THEN 0 ELSE 1 END',
            'host_failure_prediction_enabled'       => 'hs.failure_prediction_enabled',
            'host_flap_detection_enabled'           => 'hs.flap_detection_enabled',
            'host_flap_detection_enabled_changed'   => 'CASE WHEN hs.flap_detection_enabled = h.flap_detection_enabled THEN 0 ELSE 1 END',
            'host_handled'                          => 'CASE WHEN (hs.problem_has_been_acknowledged + hs.scheduled_downtime_depth) > 0 THEN 1 ELSE 0 END',
            'host_hard_state'                       => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE CASE WHEN hs.state_type = 1 THEN hs.current_state ELSE hs.last_hard_state END END',
            'host_in_downtime'                      => 'CASE WHEN (hs.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END',
            'host_is_flapping'                      => 'hs.is_flapping',
            'host_is_reachable'                     => 'hs.is_reachable',
            'host_last_check'                       => 'UNIX_TIMESTAMP(hs.last_check)',
            'host_last_hard_state'                  => 'hs.last_hard_state',
            'host_last_hard_state_change'           => 'UNIX_TIMESTAMP(hs.last_hard_state_change)',
            'host_last_notification'                => 'UNIX_TIMESTAMP(hs.last_notification)',
            'host_last_state_change'                => 'UNIX_TIMESTAMP(hs.last_state_change)',
            'host_last_time_down'                   => 'UNIX_TIMESTAMP(hs.last_time_down)',
            'host_last_time_unreachable'            => 'UNIX_TIMESTAMP(hs.last_time_unreachable)',
            'host_last_time_up'                     => 'UNIX_TIMESTAMP(hs.last_time_up)',
            'host_long_output'                      => 'hs.long_output',
            'host_max_check_attempts'               => 'hs.max_check_attempts',
            'host_modified_host_attributes'         => 'hs.modified_host_attributes',
            'host_next_check'                       => 'CASE hs.should_be_scheduled WHEN 1 THEN UNIX_TIMESTAMP(hs.next_check) ELSE NULL END',
            'host_next_notification'                => 'UNIX_TIMESTAMP(hs.next_notification)',
            'host_no_more_notifications'            => 'hs.no_more_notifications',
            'host_normal_check_interval'            => 'hs.normal_check_interval',
            'host_notifications_enabled'            => 'hs.notifications_enabled',
            'host_notifications_enabled_changed'    => 'CASE WHEN hs.notifications_enabled = h.notifications_enabled THEN 0 ELSE 1 END',
            'host_obsessing'                        => 'hs.obsess_over_host',
            'host_obsessing_changed'                => 'CASE WHEN hs.obsess_over_host = h.obsess_over_host THEN 0 ELSE 1 END',
            'host_output'                           => 'hs.output',
            'host_passive_checks_enabled'           => 'hs.passive_checks_enabled',
            'host_passive_checks_enabled_changed'   => 'CASE WHEN hs.passive_checks_enabled = h.passive_checks_enabled THEN 0 ELSE 1 END',
            'host_percent_state_change'             => 'hs.percent_state_change',
            'host_perfdata'                         => 'hs.perfdata',
            'host_problem'                          => 'CASE WHEN COALESCE(hs.current_state, 0) = 0 THEN 0 ELSE 1 END',
            'host_problem_has_been_acknowledged'    => 'hs.problem_has_been_acknowledged',
            'host_process_performance_data'         => 'hs.process_performance_data',
            'host_retry_check_interval'             => 'hs.retry_check_interval',
            'host_scheduled_downtime_depth'         => 'hs.scheduled_downtime_depth',
            'host_severity'                         => 'CASE WHEN hs.current_state = 0
            THEN
                CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL
                     THEN 16
                     ELSE 0
                END
                +
                CASE WHEN hs.problem_has_been_acknowledged = 1
                     THEN 2
                     ELSE
                        CASE WHEN hs.scheduled_downtime_depth > 0
                            THEN 1
                            ELSE 4
                        END
                END
            ELSE
                CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 16
                     WHEN hs.current_state = 1 THEN 32
                     WHEN hs.current_state = 2 THEN 64
                     ELSE 256
                END
                +
                CASE WHEN hs.problem_has_been_acknowledged = 1
                     THEN 2
                     ELSE
                        CASE WHEN hs.scheduled_downtime_depth > 0
                            THEN 1
                            ELSE 4
                        END
                END
            END
            +
            CASE WHEN hs.state_type = 1
                THEN 8
                ELSE 0
            END',
            'host_state'                => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END',
            'host_state_type'           => 'hs.state_type',
            'host_status_update_time'   => 'hs.status_update_time',
            'host_unhandled'            => 'CASE WHEN (hs.problem_has_been_acknowledged + hs.scheduled_downtime_depth) = 0 THEN 1 ELSE 0 END'
        ),
        'hostgroups' => array(
            'hostgroup'         => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_alias'   => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'    => 'hgo.name1'
        ),
        'serviceproblemsummary' => array(
            'host_unhandled_services' => 'sps.unhandled_services_count'
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('ho' => $this->prefix . 'objects'),
            array()
        )->join(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = ho.object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );
        $this->joinedVirtualTables['hosts'] = true;
    }

    /**
     * Join host status
     *
     * @return $this
     */
    protected function joinHoststatus()
    {
        $this->select->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'hs.host_object_id = ho.object_id',
            array()
        );
        return $this;
    }

    /**
     * Join host groups
     *
     * @return $this
     */
    protected function joinHostgroups()
    {
        $this->select->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = ho.object_id',
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            'hg.hostgroup_id = hgm.hostgroup_id',
            array()
        )->join(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.object_id = hg.hostgroup_object_id AND hgo.is_active = 1 AND hgo.objecttype_id = 3',
            array()
        );
        return $this;
    }

    /**
     * Join service problem summary
     *
     * @return $this
     */
    protected function joinServiceproblemsummary()
    {
        $select = <<<'SQL'
SELECT
    SUM(
        CASE WHEN(ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth + COALESCE(hs.current_state, 0)) > 0
        THEN 0
        ELSE 1
        END
    ) AS unhandled_services_count,
    SUM(
        CASE WHEN (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth + COALESCE(hs.current_state, 0) ) > 0
        THEN 1
        ELSE 0
        END
    ) AS handled_services_count,
    s.host_object_id
FROM
    icinga_servicestatus ss
    JOIN icinga_objects o ON o.object_id = ss.service_object_id
    JOIN icinga_services s ON s.service_object_id = o.object_id
    JOIN icinga_hoststatus hs ON hs.host_object_id = s.host_object_id
WHERE
    o.is_active = 1
    AND o.objecttype_id = 2
    AND ss.current_state > 0
GROUP BY
    s.host_object_id
SQL;
        $this->select->joinLeft(
            array('sps' => new Zend_Db_Expr('(' . $select . ')')),
            'sps.host_object_id = ho.object_id',
            array()
        );
        return $this;
    }
}
