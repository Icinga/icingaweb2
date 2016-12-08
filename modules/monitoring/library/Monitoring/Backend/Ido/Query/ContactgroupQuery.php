<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for contact groups
 */
class ContactgroupQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $groupBase = array('contactgroups' => array('cg.contactgroup_id', 'cgo.object_id'));

    /**
     * {@inheritdoc}
     */
    protected $groupOrigin = array('hosts', 'members', 'services');

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'contactgroups' => array(
            'contactgroup'          => 'cgo.name1 COLLATE latin1_general_ci',
            'contactgroup_name'     => 'cgo.name1',
            'contactgroup_alias'    => 'cg.alias COLLATE latin1_general_ci'
        ),
        'members' => array(
            'contact_count' => 'COUNT(cgm.contactgroup_member_id)'
        ),
        'hostgroups' => array(
            'hostgroup'         => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_alias'   => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'    => 'hgo.name1'
        ),
        'hosts' => array(
            'host'              => 'ho.name1 COLLATE latin1_general_ci',
            'host_name'         => 'ho.name1',
            'host_alias'        => 'h.alias',
            'host_display_name' => 'h.display_name COLLATE latin1_general_ci'
        ),
        'instances' => array(
            'instance_name' => 'i.instance_name'
        ),
        'servicegroups' => array(
            'servicegroup'          => 'sgo.name1 COLLATE latin1_general_ci',
            'servicegroup_name'     => 'sgo.name1',
            'servicegroup_alias'    => 'sg.alias COLLATE latin1_general_ci'
        ),
        'services' => array(
            'service'               => 'so.name2 COLLATE latin1_general_ci',
            'service_description'   => 'so.name2',
            'service_display_name'  => 's.display_name COLLATE latin1_general_ci',
            'service_host_name'     => 'so.name1'
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('cg' => $this->prefix . 'contactgroups'),
            array()
        )->join(
            array('cgo' => $this->prefix . 'objects'),
            'cgo.object_id = cg.contactgroup_object_id AND cgo.is_active = 1 AND cgo.objecttype_id = 11',
            array()
        );
        $this->joinedVirtualTables['contactgroups'] = true;
    }

    /**
     * Join contact group members
     */
    protected function joinMembers()
    {
        $this->select->joinLeft(
            array('cgm' => $this->prefix . 'contactgroup_members'),
            'cgm.contactgroup_id = cg.contactgroup_id',
            array()
        )->joinLeft(
            array('co' => $this->prefix . 'objects'),
            'co.object_id = cgm.contact_object_id AND co.is_active = 1 AND co.objecttype_id = 10',
            array()
        );
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->requireVirtualTable('hosts');
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
     * Join hosts
     */
    protected function joinHosts()
    {
        $this->select->joinLeft(
            array('hcg' => $this->prefix . 'host_contactgroups'),
            'hcg.contactgroup_object_id = cg.contactgroup_object_id',
            array()
        )->joinLeft(
            array('h' => $this->prefix . 'hosts'),
            'h.host_id = hcg.host_id',
            array()
        )->joinLeft(
            array('ho' => $this->prefix . 'objects'),
            'ho.object_id = h.host_object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
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
            'i.instance_id = cg.instance_id',
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
            'sg.servicegroup_id = sgm.servicegroup_id',
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
        $this->select->joinLeft(
            array('scg' => $this->prefix . 'service_contactgroups'),
            'scg.contactgroup_object_id = cg.contactgroup_object_id',
            array()
        )->joinLeft(
            array('s' => $this->prefix . 'services'),
            's.service_id = scg.service_id',
            array()
        )->joinLeft(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = s.service_object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
    }
}
