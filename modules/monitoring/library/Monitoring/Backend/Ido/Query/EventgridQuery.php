<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class EventgridQuery extends IdoQuery
{
    protected $columnMap = array(
        'statehistory' => array(
            'day'                  => 'DATE(sh.state_time)',
            'cnt_events'           => 'COUNT(*)',
            'objecttype_id'        => 'sho.objecttype_id',
            'cnt_up'               => 'SUM(CASE WHEN sho.objecttype_id = 1 AND sh.state = 0 THEN 1 ELSE 0 END)',
            'cnt_down_hard'        => 'SUM(CASE WHEN sho.objecttype_id = 1 AND sh.state = 1 AND state_type = 1 THEN 1 ELSE 0 END)',
            'cnt_down'             => 'SUM(CASE WHEN sho.objecttype_id = 1 AND sh.state = 1 THEN 1 ELSE 0 END)',
            'cnt_unreachable_hard' => 'SUM(CASE WHEN sho.objecttype_id = 1 AND sh.state = 2 AND state_type = 1 THEN 1 ELSE 0 END)',
            'cnt_unreachable'      => 'SUM(CASE WHEN sho.objecttype_id = 1 AND sh.state = 2 THEN 1 ELSE 0 END)',
            'cnt_unknown_hard'     => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 3 AND state_type = 1 THEN 1 ELSE 0 END)',
            'cnt_unknown'          => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 3 THEN 1 ELSE 0 END)',
            'cnt_unknown_hard'     => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 3 AND state_type = 1 THEN 1 ELSE 0 END)',
            'cnt_critical'         => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 2 THEN 1 ELSE 0 END)',
            'cnt_critical_hard'    => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 2 AND state_type = 1 THEN 1 ELSE 0 END)',
            'cnt_warning'          => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 1 THEN 1 ELSE 0 END)',
            'cnt_warning_hard'     => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 1 AND state_type = 1 THEN 1 ELSE 0 END)',
            'cnt_ok'               => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 0 THEN 1 ELSE 0 END)',
            'host'                => 'sho.name1 COLLATE latin1_general_ci',
            'service'             => 'sho.name2 COLLATE latin1_general_ci',
            'host_name'           => 'sho.name1',
            'service_description' => 'sho.name2',
            'timestamp'           => 'UNIX_TIMESTAMP(sh.state_time)'
        ),

        'servicegroups' => array(
            'servicegroup'      => 'sgo.name1 COLLATE latin1_general_ci',
            'servicegroup_name' => 'sgo.name1'
        ),

        'hostgroups' => array(
            'hostgroup'         => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_name'    => 'hgo.name1'
        )
    );

    protected function joinBaseTables()
    {
        $this->select->from(
            array('sh' => $this->prefix . 'statehistory'),
            array()
        )->join(
            array('sho' => $this->prefix . 'objects'),
            'sh.object_id = sho.object_id AND sho.is_active = 1',
            array()
        )
        ->group('DATE(sh.state_time)');
        $this->joinedVirtualTables = array('statehistory' => true);
    }

    protected function joinHostgroups()
    {
        $this->select->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = sho.object_id',
            array()
        )->join(
            array('hgs' => $this->prefix . 'hostgroups'),
            'hgm.hostgroup_id = hgs.hostgroup_id',
            array()
        )->join(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.object_id = hgs.hostgroup_object_id',
            array()
        );
    }

    protected function joinServicegroups()
    {
        $this->select->join(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = sho.object_id',
            array()
        )->join(
            array('sgs' => $this->prefix . 'servicegroups'),
            'sgm.servicegroup_id = sgs.servicegroup_id',
            array()
        )->join(
            array('sgo' => $this->prefix . 'objects'),
            'sgo.object_id = sgs.servicegroup_object_id',
            array()
        );
    }
}
