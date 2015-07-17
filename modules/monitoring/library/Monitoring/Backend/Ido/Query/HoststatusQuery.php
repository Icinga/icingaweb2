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
        'hostgroups' => array(
            'hostgroup'         => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_alias'   => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'    => 'hgo.name1'
        ),
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
            'host_notes'            => 'h.notes',
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
            'host_is_passive_checked'               => 'CASE WHEN hs.active_checks_enabled = 0 AND hs.passive_checks_enabled = 1 THEN 1 ELSE 0 END',
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
        'lasthostackcomment' => array(
            'host_last_ack' => 'hlac.last_ack_data'
        ),
        'lasthostcomment' => array(
            'host_last_comment' => 'hlc.last_comment_data'
        ),
        'lasthostdowntimecomment' => array(
            'host_last_downtime' => 'hldc.last_downtime_data'
        ),
        'lasthostflappingcomment' => array(
            'host_last_flapping' => 'hlfc.last_flapping_data'
        ),
        'servicegroups' => array(
            'servicegroup'          => 'sgo.name1 COLLATE latin1_general_ci',
            'servicegroup_name'     => 'sgo.name1',
            'servicegroup_alias'    => 'sg.alias COLLATE latin1_general_ci'
        ),
        'serviceproblemsummary' => array(
            'host_unhandled_services' => 'sps.unhandled_services_count'
        ),
        'services' => array(
            'service'                => 'so.name2 COLLATE latin1_general_ci',
            'service_description'    => 'so.name2',
            'service_display_name'   => 's.display_name COLLATE latin1_general_ci',
        )
    );

    /**
     * Create a sub query to join comments into status query
     *
     * @param   int     $entryType
     * @param   string  $alias
     *
     * @return  Zend_Db_Expr
     */
    protected function createLastCommentSubQuery($entryType, $alias)
    {
        $sql = <<<SQL
SELECT
  c.object_id,
  '[' || c.author_name || '] ' || c.comment_data AS $alias
FROM
  icinga_comments c
INNER JOIN
  (
    SELECT
      MAX(comment_id) AS comment_id,
      object_id
    FROM
      icinga_comments
    WHERE
      entry_type = $entryType
    GROUP BY object_id
  ) ec ON ec.comment_id = c.comment_id
SQL;
        return new Zend_Db_Expr('(' . $sql . ')');
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        if (version_compare($this->getIdoVersion(), '1.10.0', '<')) {
            $this->columnMap['hoststatus']['host_check_source'] = '(NULL)';
        }

        if (version_compare($this->getIdoVersion(), '1.13.0', '<')) {
            $this->columnMap['hoststatus']['host_is_reachable'] = '(NULL)';
        }

        $this->select->from(
            array('h' => $this->prefix . 'hosts'),
            array()
        )->join(
            array('ho' => $this->prefix . 'objects'),
            'ho.object_id = h.host_object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );
        $this->joinedVirtualTables['hosts'] = true;
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->select->joinLeft(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = ho.object_id',
            array()
        )->joinLeft(
            array('hg' => $this->prefix . 'hostgroups'),
            'hg.hostgroup_id = hgm.hostgroup_id',
            array()
        )->joinLeft(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.object_id = hg.hostgroup_object_id AND hgo.is_active = 1 AND hgo.objecttype_id = 3',
            array()
        );
    }

    /**
     * Join host status
     */
    protected function joinHoststatus()
    {
        $this->select->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'hs.host_object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join last host acknowledgement comment
     */
    protected function joinLasthostackcomment()
    {
        $this->select->joinLeft(
            array('hlac' => $this->createLastCommentSubQuery(4, 'last_ack_data')),
            'hlac.object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join last host comment
     */
    protected function joinLasthostcomment()
    {
        $this->select->joinLeft(
            array('hlc' => $this->createLastCommentSubQuery(1, 'last_comment_data')),
            'hlc.object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join last host downtime comment
     */
    protected function joinLasthostdowntimeComment()
    {
        $this->select->joinLeft(
            array('hldc' => $this->createLastCommentSubQuery(2, 'last_downtime_data')),
            'hldc.object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join last host flapping comment
     */
    protected function joinLasthostflappingcomment()
    {
        $this->select->joinLeft(
            array('hlfc' => $this->createLastCommentSubQuery(3, 'last_flapping_data')),
            'hlfc.object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join service groups
     */
    protected function joinServicegroups()
    {
        $this->requireVirtualTable('services');
        $this->select->joinLeft(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = s.service_object_id',
            array()
        )->joinLeft(
            array('sg' => $this->prefix . 'servicegroups'),
            'sgm.servicegroup_id = sg.' . $this->servicegroup_id,
            array()
        )->joinLeft(
            array('sgo' => $this->prefix . 'objects'),
            'sgo.object_id = sg.servicegroup_object_id AND sgo.is_active = 1 AND sgo.objecttype_id = 4',
            array()
        );
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $this->requireVirtualTable('hosts');
        $this->select->joinLeft(
            array('s' => $this->prefix . 'services'),
            's.host_object_id = h.host_object_id',
            array()
        )->joinLeft(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = s.service_object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
    }

    /**
     * Join service problem summary
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
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup()
    {
        $group = array();
        if ($this->hasJoinedVirtualTable('hostgroups') || $this->hasJoinedVirtualTable('services')) {
            $group = array('h.host_id', 'ho.object_id');
            if ($this->hasJoinedVirtualTable('hoststatus')) {
                $group[] = 'hs.hoststatus_id';
            }

            if ($this->hasJoinedVirtualTable('serviceproblemsummary')) {
                $group[] = 'sps.unhandled_services_count';
            }

            if ($this->hasJoinedVirtualTable('lasthostackcomment')) {
                $group[] = 'hlac.last_ack_data';
            }

            if ($this->hasJoinedVirtualTable('lasthostcomment')) {
                $group[] = 'hlc.last_comment_data';
            }

            if ($this->hasJoinedVirtualTable('lasthostdowntimecomment')) {
                $group[] = 'hldc.last_downtime_data';
            }

            if ($this->hasJoinedVirtualTable('lasthostflappingcomment')) {
                $group[] = 'hlfc.last_flapping_data';
            }

            if ($this->hasJoinedVirtualTable('hostgroups')) {
                $selected = false;
                foreach ($this->columns as $alias => $column) {
                    if ($column instanceof Zend_Db_Expr) {
                        continue;
                    }

                    $table = $this->aliasToTableName(
                        $this->hasAliasName($alias) ? $alias : $this->customAliasToAlias($alias)
                    );
                    if ($table === 'hostgroups') {
                        $selected = true;
                        break;
                    }
                }

                if ($selected) {
                    $group[] = 'hg.hostgroup_id';
                    $group[] = 'hgo.object_id';
                }
            }

            if ($this->hasJoinedVirtualTable('servicegroups')) {
                $selected = false;
                foreach ($this->columns as $alias => $column) {
                    if ($column instanceof Zend_Db_Expr) {
                        continue;
                    }

                    $table = $this->aliasToTableName(
                        $this->hasAliasName($alias) ? $alias : $this->customAliasToAlias($alias)
                    );
                    if ($table === 'servicegroups') {
                        $selected = true;
                        break;
                    }
                }

                if ($selected) {
                    $group[] = 'sg.servicegroup_id';
                    $group[] = 'sgo.object_id';
                }
            }
        }

        return $group;
    }
}
