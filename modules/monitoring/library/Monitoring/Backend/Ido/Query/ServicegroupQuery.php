<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class ServicegroupQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $groupBase = array('servicegroups' => array('sg.servicegroup_id', 'sgo.object_id'));

    /**
     * {@inheritdoc}
     */
    protected $groupOrigin = array('serviceobjects');

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'hostgroups' => array(
            'hostgroup'             => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_alias'       => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'        => 'hgo.name1'
        ),
        'hosts' => array(
            'host_alias'            => 'h.alias',
            'host_display_name'     => 'h.display_name COLLATE latin1_general_ci',
        ),
        'instances' => array(
            'instance_name' => 'i.instance_name'
        ),
        'servicegroups' => array(
            'servicegroup'          => 'sgo.name1 COLLATE latin1_general_ci',
            'servicegroup_alias'    => 'sg.alias COLLATE latin1_general_ci',
            'servicegroup_name'     => 'sgo.name1'
        ),
        'serviceobjects' => array(
            'host'                  => 'so.name1 COLLATE latin1_general_ci',
            'host_name'             => 'so.name1',
            'service'               => 'so.name2 COLLATE latin1_general_ci',
            'service_description'   => 'so.name2'
        ),
        'services' => array(
            'service_display_name'  => 's.display_name COLLATE latin1_general_ci',
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('sg' => $this->prefix . 'servicegroups'),
            array()
        )->join(
            array('sgo' => $this->prefix . 'objects'),
            'sg.servicegroup_object_id = sgo.object_id AND sgo.is_active = 1 AND sgo.objecttype_id = 4',
            array()
        );
        $this->joinedVirtualTables = array('servicegroups' => true);
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->requireVirtualTable('services');
        $this->select->joinLeft(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = s.host_object_id',
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
        $this->requireVirtualTable('services');
        $this->select->joinLeft(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = s.host_object_id',
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
            'i.instance_id = sg.instance_id',
            array()
        );
    }

    /**
     * Join service objects
     */
    protected function joinServiceobjects()
    {
        $this->select->joinLeft(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.' . $this->servicegroup_id . ' = sg.' . $this->servicegroup_id,
            array()
        )->joinLeft(
            array('so' => $this->prefix . 'objects'),
            'sgm.service_object_id = so.object_id',
            array()
        );
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $this->requireVirtualTable('serviceobjects');
        $this->select->joinLeft(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = so.object_id',
            array()
        );
    }
}
