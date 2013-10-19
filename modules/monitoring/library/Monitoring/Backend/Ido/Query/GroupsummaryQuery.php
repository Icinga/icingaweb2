<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class GroupsummaryQuery extends IdoQuery
{
    protected $columnMap = array(
        'hoststatus'    => array(
            'host_state'                        => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END',
            'cnt_hosts_up'                      => 'SUM(CASE WHEN hs.has_been_checked != 0 AND hs.has_been_checked IS NOT NULL AND hs.current_state = 0 THEN 1 ELSE 0 END)',
            'cnt_hosts_unreachable'             => 'SUM(CASE WHEN hs.has_been_checked != 0 AND hs.has_been_checked IS NOT NULL AND hs.current_state = 2 AND hs.problem_has_been_acknowledged + hs.scheduled_downtime_depth != 0 THEN 1 ELSE 0 END)',
            'cnt_hosts_unreachable_unhandled'   => 'SUM(CASE WHEN hs.has_been_checked != 0 AND hs.has_been_checked IS NOT NULL AND hs.current_state = 2 AND hs.problem_has_been_acknowledged + hs.scheduled_downtime_depth = 0 THEN 1 ELSE 0 END)',
            'cnt_hosts_down'                    => 'SUM(CASE WHEN hs.has_been_checked != 0 AND hs.has_been_checked IS NOT NULL AND hs.current_state = 1 AND hs.problem_has_been_acknowledged + hs.scheduled_downtime_depth != 0 THEN 1 ELSE 0 END)',
            'cnt_hosts_down_unhandled'          => 'SUM(CASE WHEN hs.has_been_checked != 0 AND hs.has_been_checked IS NOT NULL AND hs.current_state = 1 AND hs.problem_has_been_acknowledged + hs.scheduled_downtime_depth = 0 THEN 1 ELSE 0 END)',
            'cnt_hosts_pending'                 => 'SUM(CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 1 ELSE 0 END)'
        ),
        'servicestatus' => array(
            'service_state'                     => 'CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END',
            'cnt_services_ok'                   => 'SUM(CASE WHEN ss.has_been_checked != 0 AND ss.has_been_checked IS NOT NULL AND ss.current_state = 0 THEN 1 ELSE 0 END)',
            'cnt_services_unknown'              => 'SUM(CASE WHEN ss.has_been_checked != 0 AND ss.has_been_checked IS NOT NULL AND ss.current_state = 3 AND ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth != 0 THEN 1 ELSE 0 END)',
            'cnt_services_unknown_unhandled'    => 'SUM(CASE WHEN ss.has_been_checked != 0 AND ss.has_been_checked IS NOT NULL AND ss.current_state = 3 AND ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth = 0 THEN 1 ELSE 0 END)',
            'cnt_services_critical'             => 'SUM(CASE WHEN ss.has_been_checked != 0 AND ss.has_been_checked IS NOT NULL AND ss.current_state = 2 AND ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth != 0 THEN 1 ELSE 0 END)',
            'cnt_services_critical_unhandled'   => 'SUM(CASE WHEN ss.has_been_checked != 0 AND ss.has_been_checked IS NOT NULL AND ss.current_state = 2 AND ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth = 0 THEN 1 ELSE 0 END)',
            'cnt_services_warning'              => 'SUM(CASE WHEN ss.has_been_checked != 0 AND ss.has_been_checked IS NOT NULL AND ss.current_state = 1 AND ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth != 0 THEN 1 ELSE 0 END)',
            'cnt_services_warning_unhandled'    => 'SUM(CASE WHEN ss.has_been_checked != 0 AND ss.has_been_checked IS NOT NULL AND ss.current_state = 1 AND ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth = 0 THEN 1 ELSE 0 END)',
            'cnt_services_pending'              => 'SUM(CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 1 ELSE 0 END)'
        ),
        'hostgroups'    => array(
            'hostgroup_name'                    => 'hgo.name1 COLLATE latin1_general_ci'
        ),
        'servicegroups' => array(
            'servicegroup_name'                 => 'sgo.name1 COLLATE latin1_general_ci'
        )
    );

    protected function joinBaseTables()
    {
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
            'hosts'      => true,
            'hoststatus' => true
        );
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
            'so.' . $this->object_id  . ' = s.service_object_id AND so.is_active = 1',
            array()
        )->joinLeft(
            array('ss' => $this->prefix . 'servicestatus'),
            'so.' . $this->object_id . ' = ss.service_object_id',
            array()
        );
    }

    protected function joinHostgroups()
    {
        if (in_array('servicegroup_name', $this->getColumns())) {
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
            'hgm.hostgroup_id = hg.' . $this->hostgroup_id,
            array()
        )->join(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.' . $this->object_id . ' = hg.hostgroup_object_id AND hgo.is_active = 1',
            array()
            );
        $this->baseQuery->group('hgo.name1');
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
            'hgo.' . $this->object_id . ' = hg.hostgroup_object_id AND hgo.is_active = 1',
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
            'sgo.' . $this->object_id. ' = sg.servicegroup_object_id AND sgo.is_active = 1',
            array()
        );
        $this->baseQuery->group('sgo.name1');
        return $this;
    }
}

