<?php
/* Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class EmptyhostgroupQuery extends HostgroupQuery
{
    protected $subQueryTargets = [];

    protected $columnMap = [
        'hostgroups' => [
            'hostgroup'             => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_alias'       => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'        => 'hgo.name1',
            'host_name'             => '(NULL)',
            'service_description'   => '(NULL)',
            'servicegroup_name'     => '(NULL)',
            'host_contact'          => '(NULL)',
            'host_contactgroup'     => '(NULL)'
        ],
        'instances' => [
            'instance_name'         => 'i.instance_name'
        ]
    ];

    protected function joinBaseTables()
    {
        parent::joinBaseTables();

        $this->select->joinLeft(
            ['ehgm' => $this->prefix . 'hostgroup_members'],
            'ehgm.hostgroup_id = hg.hostgroup_id',
            []
        );
        $this->select->group(['hgo.object_id', 'hg.hostgroup_id']);
        $this->select->having('COUNT(ehgm.hostgroup_member_id) = ?', 0);
    }
}
