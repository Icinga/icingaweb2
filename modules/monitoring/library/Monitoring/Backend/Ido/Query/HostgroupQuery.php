<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host groups
 */
class HostgroupQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $groupBase = array('hostgroups' => array('hg.hostgroup_id'));

    /**
     * {@inheritdoc}
     */
    protected $groupOrigin = array('members');

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'hostgroups' => array(
            'hostgroup_alias'   => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'    => 'hgo.name1'
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
        )
    );


    /**
     * {@inheritdoc}
     */
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
        $this->select->joinLeft(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = ho.object_id',
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
        $this->select->joinLeft(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.hostgroup_id = hg.hostgroup_id',
            array()
        )->joinLeft(
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
        $this->select->joinLeft(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = s.service_object_id',
            array()
        )->joinLeft(
            array('sg' => $this->prefix . 'servicegroups'),
            'sgm.servicegroup_id = sg.servicegroup_id',
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
}
