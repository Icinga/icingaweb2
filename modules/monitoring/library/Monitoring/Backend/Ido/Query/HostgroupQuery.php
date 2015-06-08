<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class HostgroupQuery extends IdoQuery
{
    protected $columnMap = array(
        'hostgroups' => array(
            'hostgroups'      => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_name'  => 'hgo.name1',
            'hostgroup_alias' => 'hg.alias',
            'hostgroup_id'    => 'hg.hostgroup_id'
        ),
        'hosts' => array(
            'host'            => 'ho.name1 COLLATE latin1_general_ci',
            'host_name'       => 'ho.name1'
        )
    );

    protected function joinBaseTables()
    {
        $this->select->from(
            array('hg' => $this->prefix . 'hostgroups'),
            array()
        )->join(
            array('hgo' => $this->prefix . 'objects'),
            'hg.hostgroup_object_id = hgo.' . $this->object_id . ' AND hgo.is_active = 1',
            array()
        );
        $this->joinedVirtualTables = array('hostgroups' => true);
    }

    protected function joinHosts()
    {
        $this->select->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.hostgroup_id = hg.hostgroup_id',
            array()
        )->join(
            array('ho' => $this->prefix . 'objects'),
            'hgm.host_object_id = ho.object_id AND ho.is_active = 1',
            array()
        );
    }
}
