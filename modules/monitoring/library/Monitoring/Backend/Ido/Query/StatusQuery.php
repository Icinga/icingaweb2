<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class StatusQuery extends AbstractQuery
{
    protected $allowCustomVars = true;

    protected $columnMap = array(
        'hosts' => array(
            'host'                  => 'ho.name1 COLLATE latin1_general_ci',
            'host_name'             => 'ho.name1 COLLATE latin1_general_ci',
            'host_display_name'     => 'h.display_name',
            'host_alias'            => 'h.alias',
            'host_address'          => 'h.address',
            'host_ipv4'             => 'INET_ATON(h.address)',
            'host_icon_image'       => 'h.icon_image',
            'host_action_url'       => 'h.action_url',
            'host_notes_url'        => 'h.notes_url'
        ),
        'hoststatus' => array(
            'host_state' => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END',
            'host_output' => 'hs.output',
            'host_long_output' => 'hs.long_output',
            'host_perfdata' => 'hs.perfdata',
            'host_acknowledged' => 'hs.problem_has_been_acknowledged',
            'host_in_downtime' => 'CASE WHEN (hs.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END',
            'host_handled' => 'CASE WHEN (hs.problem_has_been_acknowledged + hs.scheduled_downtime_depth) > 0 THEN 1 ELSE 0 END',
            'host_does_active_checks' => 'hs.active_checks_enabled',
            'host_accepts_passive_checks' => 'hs.passive_checks_enabled',
            'host_last_state_change' => 'UNIX_TIMESTAMP(hs.last_state_change)',
            'host_check_command' => 'hs.check_command',
            'host_current_check_attempt' => 'hs.current_check_attempt',
            'host_max_check_attempts' => 'hs.max_check_attempts',
            'host_attempt' => 'hs.current_check_attempt || \'/\' || hs.max_check_attempts',
            'host_last_check' => 'hs.last_check',
            'host_next_check' => 'hs.next_check',
            'host_check_type' => 'hs.check_type',
            'host_last_hard_state_change' => 'hs.last_hard_state_change',
            'host_last_hard_state' => 'hs.last_hard_state',
            'host_last_time_up' => 'hs.last_time_up',
            'host_last_time_down' => 'hs.last_time_down',
            'host_last_time_unreachable' => 'hs.last_time_unreachable',
            'host_state_type' => 'hs.state_type',
            'host_last_notification' => 'hs.last_notification',
            'host_next_notification' => 'hs.next_notification',
            'host_no_more_notifications' => 'hs.no_more_notifications',
            'host_notifications_enabled' => 'hs.notifications_enabled',
            'host_problem_has_been_acknowledged' => 'hs.problem_has_been_acknowledged',
            'host_acknowledgement_type' => 'hs.acknowledgement_type',
            'host_current_notification_number' => 'hs.current_notification_number',
            'host_passive_checks_enabled' => 'hs.passive_checks_enabled',
            'host_active_checks_enabled' => 'hs.active_checks_enabled',
            'host_event_handler_enabled' => 'hs.event_handler_enabled',
            'host_flap_detection_enabled' => 'hs.flap_detection_enabled',
            'host_is_flapping' => 'hs.is_flapping',
            'host_percent_state_change' => 'hs.percent_state_change',
            'host_check_latency' => 'hs.latency',
            'host_check_execution_time' => 'hs.execution_time',
            'host_scheduled_downtime_depth' => 'hs.scheduled_downtime_depth',
            'host_failure_prediction_enabled' => 'hs.failure_prediction_enabled',
            'host_process_performance_data' => 'hs.process_performance_data',
            'host_obsessing' => 'hs.obsess_over_host',
            'host_modified_host_attributes' => 'hs.modified_host_attributes',
            'host_event_handler' => 'hs.event_handler',
            'host_check_command' => 'hs.check_command',
            'host_normal_check_interval' => 'hs.normal_check_interval',
            'host_retry_check_interval' => 'hs.retry_check_interval',
            'host_check_timeperiod_object_id' => 'hs.check_timeperiod_object_id',
            'host_status_update_time' => 'hs.status_update_time',
            'host_problem' => 'CASE WHEN hs.current_state = 0 THEN 0 ELSE 1 END',
            'host_severity' => 'CASE WHEN hs.current_state = 0
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
            END',
        ),
        'hostgroups' => array(
            'hostgroups' => 'hgo.name1',
        ),
        'servicegroups' => array(
            'servicegroups' => 'sgo.name1',
        ),
        'services' => array(
            'service_host_name'      => 'so.name1 COLLATE latin1_general_ci',
            'service'                => 'so.name2 COLLATE latin1_general_ci',
            'service_description'    => 'so.name2 COLLATE latin1_general_ci',
            'service_display_name'   => 's.display_name',
            'service_icon_image'     => 's.icon_image',
            'service_action_url'     => 's.action_url',
            'service_notes_url'      => 's.notes_url'

        ),
        'servicestatus' => array(
            'service_state' => 'CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END',
            'service_output' => 'ss.output',
            'service_long_output' => 'ss.long_output',
            'service_perfdata' => 'ss.perfdata',
            'service_acknowledged' => 'ss.problem_has_been_acknowledged',
            'service_in_downtime' => 'CASE WHEN (ss.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END',
            'service_handled' => 'CASE WHEN (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth) > 0 THEN 1 ELSE 0 END',
            'service_does_active_checks' => 'ss.active_checks_enabled',
            'service_accepts_passive_checks' => 'ss.passive_checks_enabled',
            'service_last_state_change' => 'UNIX_TIMESTAMP(ss.last_state_change)',
            'service_check_command' => 'ss.check_command',
            'service_last_time_ok' => 'ss.last_time_ok',
            'service_last_time_warning' => 'ss.last_time_warning',
            'service_last_time_critical' => 'ss.last_time_critical',
            'service_last_time_unknown' => 'ss.last_time_unknown',
            'service_current_check_attempt' => 'ss.current_check_attempt',
            'service_max_check_attempts' => 'ss.max_check_attempts',
            'service_attempt' => 'ss.current_check_attempt || \'/\' || ss.max_check_attempts',
            'service_last_check' => 'ss.last_check',
            'service_next_check' => 'ss.next_check',
            'service_check_type' => 'ss.check_type',
            'service_last_hard_state_change' => 'ss.last_hard_state_change',
            'service_last_hard_state' => 'ss.last_hard_state',
            'service_state_type' => 'ss.state_type',
            'service_last_notification' => 'ss.last_notification',
            'service_next_notification' => 'ss.next_notification',
            'service_no_more_notifications' => 'ss.no_more_notifications',
            'service_notifications_enabled' => 'ss.notifications_enabled',
            'service_problem_has_been_acknowledged' => 'ss.problem_has_been_acknowledged',
            'service_acknowledgement_type' => 'ss.acknowledgement_type',
            'service_current_notification_number' => 'ss.current_notification_number',
            'service_passive_checks_enabled' => 'ss.passive_checks_enabled',
            'service_active_checks_enabled' => 'ss.active_checks_enabled',
            'service_event_handler_enabled' => 'ss.event_handler_enabled',
            'service_flap_detection_enabled' => 'ss.flap_detection_enabled',
            'service_is_flapping' => 'ss.is_flapping',
            'service_percent_state_change' => 'ss.percent_state_change',
            'service_check_latency' => 'ss.latency',
            'service_check_execution_time' => 'ss.execution_time',
            'service_scheduled_downtime_depth' => 'ss.scheduled_downtime_depth',
            'service_failure_prediction_enabled' => 'ss.failure_prediction_enabled',
            'service_process_performance_data' => 'ss.process_performance_data',
            'service_obsessing' => 'ss.obsess_over_service',
            'service_modified_service_attributes' => 'ss.modified_service_attributes',
            'service_event_handler' => 'ss.event_handler',
            'service_check_command' => 'ss.check_command',
            'service_normal_check_interval' => 'ss.normal_check_interval',
            'service_retry_check_interval' => 'ss.retry_check_interval',
            'service_check_timeperiod_object_id' => 'ss.check_timeperiod_object_id',
            'service_status_update_time' => 'ss.status_update_time'
        ),
        'serviceproblemsummary' => array(
            'host_unhandled_service_count' => 'sps.unhandled_service_count'
        ),
        'lasthostcomment' => array(
            'host_last_comment' => 'hlc.comment_id'
        ),
        'lastservicecomment' => array(
            'service_last_comment' => 'slc.comment_id'
        ),
        'status' => array(
            'service_problems' => 'CASE WHEN ss.current_state = 0 THEN 0 ELSE 1 END',
            'service_handled' => 'CASE WHEN ss.problem_has_been_acknowledged = 1 OR ss.scheduled_downtime_depth > 0 THEN 1 ELSE 0 END',
            'service_severity' => 'CASE WHEN ss.current_state = 0
            THEN
                CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL
                     THEN 16
                     ELSE 0
                END
                +
                CASE WHEN ss.problem_has_been_acknowledged = 1
                     THEN 2
                     ELSE
                        CASE WHEN ss.scheduled_downtime_depth > 0
                            THEN 1
                            ELSE 4
                        END
                END
            ELSE
                CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 16
                     WHEN ss.current_state = 1 THEN 32
                     WHEN ss.current_state = 2 THEN 128
                     WHEN ss.current_state = 3 THEN 64
                     ELSE 256
                END
                +
                CASE WHEN ss.problem_has_been_acknowledged = 1
                     THEN 2
                     ELSE
                        CASE WHEN ss.scheduled_downtime_depth > 0
                            THEN 1
                            ELSE 4
                        END
                END
            END',
        ),
    );

    public function group($col)
    {
        $this->baseQuery->group($col);
    }

    protected function getDefaultColumns()
    {
        return $this->columnMap['hosts'];
        /*
             + $this->columnMap['services']
             + $this->columnMap['hoststatus']
             + $this->columnMap['servicestatus']
             ;*/
    }

    protected function joinBaseTables()
    {
        // TODO: Shall we always add hostobject?
        $this->baseQuery = $this->db->select()->from(
            array('ho' => $this->prefix . 'objects'),
            array()
        )->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'ho.object_id = hs.host_object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        )->join(
            array('h' => $this->prefix . 'hosts'),
            'hs.host_object_id = h.host_object_id',
            array()
            );
        $this->joinedVirtualTables = array(
            'hosts' => true,
            'hoststatus' => true,
        );
    }

    protected function joinStatus()
    {
        $this->requireVirtualTable('services');
    }

    protected function joinServiceStatus()
    {
        $this->requireVirtualTable('services');
    }

    protected function joinServices()
    {
        $this->baseQuery->join(
            array('s' => $this->prefix . 'services'),
            's.host_object_id = h.host_object_id',
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.'.$this->object_id.' = s.service_object_id AND so.is_active = 1',
            array()
        )->joinLeft(
            array('ss' => $this->prefix . 'servicestatus'),
            'so.'.$this->object_id.' = ss.service_object_id',
            array()
        );
    }

    // TODO: Test this one, doesn't seem to work right now
    protected function joinHostgroups()
    {
        if ($this->hasJoinedVirtualTable('services')) {
            return $this->joinServiceHostgroups();
        } else {
            return $this->joinHostHostgroups();
        }
    }

    protected function joinHostHostgroups()
    {
        $this->baseQuery->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = h.host_object_id',
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            'hgm.hostgroup_id = hg'.$this->hostgroup_id,
            array()
        );

        return $this;
    }

    protected function joinServiceHostgroups()
    {
        $this->baseQuery->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = s.host_object_id',
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            'hgm.hostgroup_id = hg.' . $this->hostgroup_id,
            array()
        )->join(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.' . $this->object_id . ' = hg.hostgroup_object_id'
            . ' AND hgo.is_active = 1',
            array()
        );

        return $this;
    }

    protected function joinServicegroups()
    {
        $this->requireVirtualTable('services');
        $this->baseQuery->join(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = s.service_object_id',
            array()
        )->join(
            array('sg' => $this->prefix . 'servicegroups'),
            'sgm.servicegroup_id = sg.' . $this->servicegroup_id,
            array()
        )->join(
            array('sgo' => $this->prefix . 'objects'),
            'sgo.' . $this->object_id. ' = sg.servicegroup_object_id'
            . ' AND sgo.is_active = 1',
            array()
        );

        return $this;
    }

    protected function joinServiceproblemsummary()
    {
        $this->baseQuery->joinleft(
            array ('sps' => new \Zend_Db_Expr(
                '(SELECT COUNT(s.service_object_id) as unhandled_service_count, s.host_object_id as host_object_id '.
                'FROM icinga_services s INNER JOIN icinga_servicestatus ss ON ss.current_state != 0 AND '.
                'ss.problem_has_been_acknowledged = 0 AND ss.scheduled_downtime_depth = 0 AND '.
                'ss.service_object_id = s.service_object_id '.
                'GROUP BY s.host_object_id'.
                ')')),
            'sps.host_object_id = hs.host_object_id',
            array()
        );
    }

    protected function joinLasthostcomment()
    {
        $this->baseQuery->joinleft(
            array ('hlc' => new \Zend_Db_Expr(
                '(SELECT MAX(c.comment_id) as comment_id, c.object_id '.
                'FROM icinga_comments c GROUP BY c.object_id)')
            ),
            'hlc.object_id = hs.host_object_id',
            array()
        );
    }

    protected function joinLastservicecomment()
    {
        $this->baseQuery->joinleft(
            array ('slc' => new \Zend_Db_Expr(
                '(SELECT MAX(c.comment_id) as comment_id, c.object_id '.
                'FROM icinga_comments c GROUP BY c.object_id)')
            ),
            'slc.object_id = ss.service_object_id',
            array()
        );
    }
}
// @codingStandardsIgnoreStop
