<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class ServicestatusQuery extends AbstractQuery
{
    protected $allowCustomVars = true;

    protected $columnMap = array(
        'services' => array(
            'service_host_name'      => 'so.name1 COLLATE latin1_general_ci',
            'service'                => 'so.name2 COLLATE latin1_general_ci',
            'service_description'    => 'so.name2 COLLATE latin1_general_ci',
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
            'service_last_hard_state'        => 'ss.last_hard_state',
            'service_last_hard_state_change' => 'UNIX_TIMESTAMP(ss.last_hard_state_change)',
            'service_check_command'          => 'ss.check_command',
            'service_last_check'             => 'UNIX_TIMESTAMP(ss.last_check)',
            'service_next_check'             => 'CASE WHEN ss.should_be_scheduled THEN UNIX_TIMESTAMP(ss.next_check) ELSE NULL END',
            'service_check_execution_time'   => 'ss.execution_time',
            'service_check_latency'          => 'ss.latency',
            'service_notifications_enabled'  => 'ss.notifications_enabled',
            'service_last_time_ok'           => 'ss.last_time_ok',
            'service_last_time_warning'      => 'ss.last_time_warning',
            'service_last_time_critical'     => 'ss.last_time_critical',
            'service_last_time_unknown'      => 'ss.last_time_unknown',
        ),
        'servicegroups' => array(
            'servicegroups' => 'sgo.name1',
        ),
    );

    protected function getDefaultColumns()
    {
        return $this->columnMap['services']
             + $this->columnMap['servicestatus'];
    }

    protected function joinBaseTables()
    {
        // TODO: Shall we always add hostobject?
        $this->baseQuery = $this->db->select()->from(
            array('so' => $this->prefix . 'objects'),
            array()
        )->join(
            array('ss' => $this->prefix . 'servicestatus'),
            'so.object_id = ss.service_object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        )->join(
            array('s' => $this->prefix . 'services'),
            'ss.service_object_id = s.service_object_id',
            array()
        );
        $this->joinedVirtualTables = array(
            'services'      => true,
            'servicestatus' => true,
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
