<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
            'sg.servicegroup_object_id = sgo.object_id',
            array()
        )->where(
            'sgo.is_active = ?',
            1
        )
        ->where(
            'sgo.objecttype_id = ?',
            4
        );
        $this->joinedVirtualTables = array('servicegroups' => true);
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->requireVirtualTable('services');
        $this->select->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = s.host_object_id',
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            'hg.hostgroup_id = hgm.hostgroup_id',
            array()
        )->join(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.object_id = hg.hostgroup_object_id',
            array()
        )->where(
            'hgo.is_active = ?',
            1
        )
        ->where(
            'hgo.objecttype_id = ?',
            3
        );
    }

    /**
     * Join hosts
     */
    protected function joinHosts()
    {
        $this->requireVirtualTable('services');
        $this->select->join(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = s.host_object_id',
            array()
        );
    }

    /**
     * Join service objects
     */
    protected function joinServiceobjects()
    {
        $this->select->join(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.' . $this->servicegroup_id . ' = sg.' . $this->servicegroup_id,
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'sgm.service_object_id = so.object_id',
            array()
        );
        $this->group('sgo.name1');
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $this->requireVirtualTable('serviceobjects');
        $this->select->join(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = so.object_id',
            array()
        );
    }
}
