<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host groups
 */
class HostgroupQuery extends IdoQuery
{
    protected $allowCustomVars = true;

    protected $groupBase = array(
        'hostgroups'         => array('hgo.object_id', 'hg.hostgroup_id'),
        'hoststatus'         => array('hs.hoststatus_id'),
        'servicestatus'      => array('ss.servicestatus_id')
    );

    protected $groupOrigin = array('members');

    protected $columnMap = array(
        'hostgroups' => array(
            'hostgroup_alias'   => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'    => 'hgo.name1'
        ),
        'hoststatus' => array(
            'host_handled'  => 'CASE WHEN (hs.problem_has_been_acknowledged + hs.scheduled_downtime_depth) > 0 THEN 1 ELSE 0 END',
            'host_state'    => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END'
        ),
        'instances' => array(
            'instance_name' => 'i.instance_name'
        ),
        'members' => array(
            'host_name' => 'ho.name1'
        ),
        'servicegroups' => array(
            'servicegroup_name' => 'sgo.name1'
        ),
        'services' => array(
            'service_description' => 'so.name2'
        ),
        'servicestatus' => array(
            'service_handled'   => 'CASE WHEN (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth + COALESCE(hs.current_state, 0)) > 0 THEN 1 ELSE 0 END',
            'service_state'     => 'CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END'
        )
    );

    protected function joinBaseTables()
    {
        $this->select->from(
            array('hgo' => $this->prefix . 'objects'),
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            'hg.hostgroup_object_id = hgo.object_id AND hgo.is_active = 1 AND hgo.objecttype_id = 3',
            array()
        );
        $this->joinedVirtualTables['hostgroups'] = true;
    }

    /**
     * Join hosts
     */
    protected function joinHosts()
    {
        $this->requireVirtualTable('members');
        $this->select->join(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join host status
     */
    protected function joinHoststatus()
    {
        $this->requireVirtualTable('members');
        $this->select->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'hs.host_object_id = ho.object_id',
            array()
        );
    }

    /**
     * Join instances
     */
    protected function joinInstances()
    {
        $this->select->join(
            array('i' => $this->prefix . 'instances'),
            'i.instance_id = hg.instance_id',
            array()
        );
    }

    /**
     * Join members
     */
    protected function joinMembers()
    {
        $this->select->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.hostgroup_id = hg.hostgroup_id',
            array()
        )->join(
            array('ho' => $this->prefix . 'objects'),
            'hgm.host_object_id = ho.object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );
    }

    /**
     * Join service groups
     */
    protected function joinServicegroups()
    {
        $this->requireVirtualTable('services');
        $this->select->join(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = s.service_object_id',
            array()
        )->join(
            array('sg' => $this->prefix . 'servicegroups'),
            'sgm.servicegroup_id = sg.servicegroup_id',
            array()
        )->join(
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
        $this->select->join(
            array('s' => $this->prefix . 'services'),
            's.host_object_id = h.host_object_id',
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = s.service_object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
    }

    /**
     * Join service status
     */
    protected function joinServicestatus()
    {
        $this->requireVirtualTable('services');
        $this->requireVirtualTable('hoststatus');
        $this->select->join(
            array('ss' => $this->prefix . 'servicestatus'),
            'ss.service_object_id = so.object_id',
            array()
        );
    }
}
