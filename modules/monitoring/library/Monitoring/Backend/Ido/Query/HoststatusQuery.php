<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class HoststatusQuery extends AbstractQuery
{
    protected $allowCustomVars = true;

    protected $columnMap = array(
        'hosts' => array(
            'host'                   => 'ho.name1 COLLATE latin1_general_ci',
            'host_name'              => 'ho.name1 COLLATE latin1_general_ci',
            'host_display_name'      => 'h.display_name',
            'host_alias'             => 'h.alias',
            'host_address'           => 'h.address',
            'host_ipv4'              => 'INET_ATON(h.address)',
            'host_icon_image'        => 'h.icon_image',
        ),
        'hoststatus' => array(
            'problems'                   => 'CASE WHEN hs.current_state = 0 THEN 0 ELSE 1 END',
            'handled'                    => 'CASE WHEN (hs.problem_has_been_acknowledged + hs.scheduled_downtime_depth) > 0 THEN 1 ELSE 0 END',
            'unhandled'                  => 'CASE WHEN (hs.problem_has_been_acknowledged + hs.scheduled_downtime_depth) = 0 THEN 1 ELSE 0 END',
            'host_state'                  => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END',
            'host_output'                 => 'hs.output',
            'host_long_output'            => 'hs.long_output',
            'host_perfdata'               => 'hs.perfdata',
            'host_problem'                => 'CASE WHEN hs.current_state = 0 THEN 0 ELSE 1 END',
            'host_acknowledged'           => 'hs.problem_has_been_acknowledged',
            'host_in_downtime'            => 'CASE WHEN (hs.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END',
            'host_handled'                => 'CASE WHEN (hs.problem_has_been_acknowledged + hs.scheduled_downtime_depth) > 0 THEN 1 ELSE 0 END',
            'host_does_active_checks'     => 'hs.active_checks_enabled',
            'host_accepts_passive_checks' => 'hs.passive_checks_enabled',
            'host_last_state_change'      => 'UNIX_TIMESTAMP(hs.last_state_change)',
            'host_last_hard_state'        => 'hs.last_hard_state',
            'host_check_command'          => 'hs.check_command',
            'host_last_check'             => 'UNIX_TIMESTAMP(hs.last_check)',
            'host_next_check'             => 'CASE WHEN hs.should_be_scheduled THEN UNIX_TIMESTAMP(hs.next_check) ELSE NULL END',
            'host_check_execution_time'   => 'hs.execution_time',
            'host_check_latency'          => 'hs.latency',
            'host_notifications_enabled'  => 'hs.notifications_enabled',
            'host_last_time_up'           => 'hs.last_time_up',
            'host_last_time_down'         => 'hs.last_time_down',
            'host_last_time_unreachable'  => 'hs.last_time_unreachable',

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
            'hostgroup' => 'hgo.name1 COLLATE latin1_general_ci',
        ),
        'services' => array(
            'services_cnt'      => 'SUM(1)',
            'services_ok'       => 'SUM(CASE WHEN ss.current_state = 0 THEN 1 ELSE 0 END)',
            'services_warning'  => 'SUM(CASE WHEN ss.current_state = 1 THEN 1 ELSE 0 END)',
            'services_critical' => 'SUM(CASE WHEN ss.current_state = 2 THEN 1 ELSE 0 END)',
            'services_unknown'  => 'SUM(CASE WHEN ss.current_state = 3 THEN 1 ELSE 0 END)',
            'services_pending'  => 'SUM(CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 1 ELSE 0 END)',
            'services_problem'  => 'SUM(CASE WHEN ss.current_state > 0 THEN 1 ELSE 0 END)',
            'services_problem_handled'  => 'SUM(CASE WHEN ss.current_state > 0 AND (ss.problem_has_been_acknowledged = 1 OR ss.scheduled_downtime_depth > 0) THEN 1 ELSE 0 END)',
            'services_problem_unhandled'  => 'SUM(CASE WHEN ss.current_state > 0 AND (ss.problem_has_been_acknowledged = 0 AND ss.scheduled_downtime_depth = 0) THEN 1 ELSE 0 END)',
            'services_warning_handled'  => 'SUM(CASE WHEN ss.current_state = 1 AND (ss.problem_has_been_acknowledged = 1 OR ss.scheduled_downtime_depth > 0) THEN 1 ELSE 0 END)',
            'services_critical_handled' => 'SUM(CASE WHEN ss.current_state = 2 AND (ss.problem_has_been_acknowledged = 1 OR ss.scheduled_downtime_depth > 0) THEN 1 ELSE 0 END)',
            'services_unknown_handled'  => 'SUM(CASE WHEN ss.current_state = 3 AND (ss.problem_has_been_acknowledged = 1 OR ss.scheduled_downtime_depth > 0) THEN 1 ELSE 0 END)',
            'services_warning_unhandled'  => 'SUM(CASE WHEN ss.current_state = 1 AND (ss.problem_has_been_acknowledged = 0 AND ss.scheduled_downtime_depth = 0) THEN 1 ELSE 0 END)',
            'services_critical_unhandled' => 'SUM(CASE WHEN ss.current_state = 2 AND (ss.problem_has_been_acknowledged = 0 AND ss.scheduled_downtime_depth = 0) THEN 1 ELSE 0 END)',
            'services_unknown_unhandled'  => 'SUM(CASE WHEN ss.current_state = 3 AND (ss.problem_has_been_acknowledged = 0 AND ss.scheduled_downtime_depth = 0) THEN 1 ELSE 0 END)',
        ),
    );

    protected $aggregateColumnIdx = array(
        'services_cnt'               => true,
        'services_problem'           => true,
        'services_problem_handled'   => true,
        'services_problem_unhandled' => true,
    );

    protected function getDefaultColumns()
    {
        return $this->columnMap['hosts']
             + $this->columnMap['hoststatus'];
    }

    protected function joinBaseTables()
    {
        // TODO: Shall we always add hostobject?
        $this->baseQuery = $this->db->select()->from(
            array('ho' => $this->prefix . 'objects'),
            array()
        )->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'ho.' . $this->object_id . ' = hs.host_object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        )->join(
            array('h' => $this->prefix . 'hosts'),
            'hs.host_object_id = h.host_object_id',
            array()
        );
        $this->joinedVirtualTables = array(
            'hosts'      => true,
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
            "so.$this->object_id = s.service_object_id AND so.is_active = 1",
            array()
        )->joinLeft(
            array('ss' => $this->prefix . 'servicestatus'),
            "so.$this->object_id = ss.service_object_id",
            array()
        );
        foreach ($this->columns as $col) {
            $real = $this->aliasToColumnName($col);
            if (substr($real, 0, 4) === 'SUM(') {
                continue;
            }
            $this->baseQuery->group($real);
        }
        $this->uglySlowConservativeCount = true;
    }

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
            "hgm.hostgroup_id = hg.$this->hostgroup_id",
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
            'hgo.' . $this->object_id. ' = hg.hostgroup_object_id'
          . ' AND hgo.is_active = 1',
            array()
        );

        return $this;
    }

    protected function joinServicegroups()
    {
        // TODO: Only hosts with services having such servicegroups
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
}
