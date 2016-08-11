<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Application\Config;

class ServicegroupQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $groupBase = array('servicegroups' => array('sg.servicegroup_id'), 'servicestatus' => array('ss.servicestatus_id'));

    /**
     * {@inheritdoc}
     */
    protected $groupOrigin = array('members');

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'hostgroups' => array(
            'hostgroup_name' => 'hgo.name1'
        ),
        'instances' => array(
            'instance_name' => 'i.instance_name'
        ),
        'members' => array(
            'host_name'             => 'so.name1',
            'service_description'   => 'so.name2'
        ),
        'servicegroups' => array(
            'servicegroup_alias'    => 'sg.alias COLLATE latin1_general_ci',
            'servicegroup_name'     => 'sgo.name1'
        ),
        'servicestatus' => array(
            'service_handled'   => 'CASE WHEN (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth + COALESCE(hs.current_state, 0)) > 0 THEN 1 ELSE 0 END',
            'service_state'     => 'CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END'
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        if ((bool) Config::module('monitoring')->get('ido', 'use_optimized_queries', false)) {
            $this->columnMap['servicegroups']['servicegroup_alias'] = 'sg.alias';
            $this->columnMap['servicestatus']['service_state'] = 'ss.current_state';
        }

        $this->select->from(
            array('sgo' => $this->prefix . 'objects'),
            array()
        )->join(
            array('sg' => $this->prefix . 'servicegroups'),
            'sg.servicegroup_object_id = sgo.object_id AND sgo.objecttype_id = 4 AND sgo.is_active = 1',
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
            'hgo.object_id = hg.hostgroup_object_id AND hgo.objecttype_id = 3 AND hgo.is_active = 1',
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
    protected function joinMembers()
    {
        $this->select->joinLeft(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.servicegroup_id = sg.servicegroup_id',
            array()
        )->joinLeft(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = sgm.service_object_id AND so.objecttype_id = 2 AND so.is_active = 1',
            array()
        );
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $this->requireVirtualTable('members');
        $this->select->joinLeft(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = so.object_id',
            array()
        );
    }

    /**
     * Join service status
     */
    protected function joinServicestatus()
    {
        $this->requireVirtualTable('services');
        $this->select->joinLeft(
            array('hs' => $this->prefix . 'hoststatus'),
            'hs.host_object_id = s.host_object_id',
            array()
        );
        $this->select->joinLeft(
            array('ss' => $this->prefix . 'servicestatus'),
            'ss.service_object_id = so.object_id',
            array()
        );
    }
}
