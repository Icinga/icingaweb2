<?php

namespace Icinga\Monitoring\Backend\Ido\Query;

class StatusQuery extends AbstractQuery
{
    protected $allowCustomVars = true;

    protected $columnMap = array(
        'hosts' => array(
            'host'                   => 'ho.name1',
            'host_name'              => 'ho.name1',
            'host_display_name'      => 'h.display_name',
            'host_alias'             => 'h.alias',
            'host_address'           => 'h.address',
            'host_ipv4'              => 'INET_ATON(h.address)',
            'host_icon_image'        => 'h.icon_image',
        ),
        'hoststatus' => array(
            'host_state'                  => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END',
            'host_output'                 => 'hs.output',
            'host_long_output'            => 'hs.long_output',
            'host_perfdata'               => 'hs.perfdata',
            'host_acknowledged'           => 'hs.problem_has_been_acknowledged',
            'host_in_downtime'           => 'CASE WHEN (hs.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END',
            'host_handled'        => 'CASE WHEN (hs.problem_has_been_acknowledged + hs.scheduled_downtime_depth) > 0 THEN 1 ELSE 0 END',
            'host_does_active_checks'     => 'hs.active_checks_enabled',
            'host_accepts_passive_checks' => 'hs.passive_checks_enabled',
            'host_last_state_change'      => 'UNIX_TIMESTAMP(hs.last_state_change)',
            'host_check_command'          => 'hs.check_command',
            'host_problems'               => 'CASE WHEN hs.current_state = 0 THEN 0 ELSE 1 END',
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
        'services' => array(
            'service_host_name'      => 'so.name1',
            'service'                => 'so.name2',
            'service_description'    => 'so.name2',
            'service_display_name'   => 's.display_name',
            'service_icon_image'     => 's.icon_image',
        ),
        'servicestatus' => array(
            'current_state'          => 'CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END',
            'service_state'          => 'CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END',
            'service_hard_state'     => 'CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 99 ELSE CASE WHEN ss.state_type = 1 THEN ss.current_state ELSE ss.last_hard_state END END',
            'service_state_type'     => 'ss.state_type',
            'service_output'         => 'ss.output',
            'service_long_output'    => 'ss.long_output',
            'service_perfdata'       => 'ss.perfdata',
            'service_acknowledged'   => 'ss.problem_has_been_acknowledged',
            'service_in_downtime'    => 'CASE WHEN (ss.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END',
            'service_handled'        => 'CASE WHEN (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth + COALESCE(hs.current_state, 0)) > 0 THEN 1 ELSE 0 END',
            'service_does_active_checks'     => 'ss.active_checks_enabled',
            'service_accepts_passive_checks' => 'ss.passive_checks_enabled',
            'service_last_state_change'      => 'UNIX_TIMESTAMP(ss.last_state_change)',
            'service_last_hard_state_change' => 'UNIX_TIMESTAMP(ss.last_hard_state_change)',
            'service_check_command'          => 'ss.check_command',
            'service_last_check'             => 'UNIX_TIMESTAMP(ss.last_check)',
            'service_next_check'             => 'CASE WHEN ss.should_be_scheduled THEN UNIX_TIMESTAMP(ss.next_check) ELSE NULL END',
            'service_check_execution_time'   => 'ss.execution_time',
            'service_check_latency'          => 'ss.latency',
        ),
        'status' => array(
            'problems' => 'CASE WHEN ss.current_state = 0 THEN 0 ELSE 1 END',
            'handled'  => 'CASE WHEN ss.problem_has_been_acknowledged = 1 OR ss.scheduled_downtime_depth > 0 THEN 1 ELSE 0 END',
            'severity' => 'CASE WHEN ss.current_state = 0
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
        )
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
            'ho.object_id = hs.host_object_id AND ho.is_active = 1',
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
        $this->baseQuery->join(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = s.service_object_id',
            array()
        )->join(
            array('sg' => $this->prefix . 'servicegroups'),
            'sgm.servicegroup_id = sg.' . $this->servicegroup_id,
            array()
        )->join(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.' . $this->object_id. ' = hg.' . $this->hostgroup_id
          . ' AND hgo.is_active = 1',
            array()
        );

        return $this;
    }
}
