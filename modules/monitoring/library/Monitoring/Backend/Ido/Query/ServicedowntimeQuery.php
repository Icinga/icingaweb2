<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for service downtimes
 */
class ServicedowntimeQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $groupBase = array('downtimes' => array('sd.scheduleddowntime_id', 'so.object_id'));

    /**
     * {@inheritdoc}
     */
    protected $groupOrigin = array('hostgroups', 'servicegroups');

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'downtimes' => array(
            'downtime_author'           => 'sd.author_name COLLATE latin1_general_ci',
            'downtime_author_name'      => 'sd.author_name',
            'downtime_comment'          => 'sd.comment_data',
            'downtime_duration'         => 'sd.duration',
            'downtime_end'              => 'CASE WHEN sd.is_fixed > 0 THEN UNIX_TIMESTAMP(sd.scheduled_end_time) ELSE UNIX_TIMESTAMP(sd.trigger_time) + sd.duration END',
            'downtime_entry_time'       => 'UNIX_TIMESTAMP(sd.entry_time)',
            'downtime_internal_id'      => 'sd.internal_downtime_id',
            'downtime_is_fixed'         => 'sd.is_fixed',
            'downtime_is_flexible'      => 'CASE WHEN sd.is_fixed = 0 THEN 1 ELSE 0 END',
            'downtime_is_in_effect'     => 'sd.is_in_effect',
            'downtime_scheduled_end'    => 'UNIX_TIMESTAMP(sd.scheduled_end_time)',
            'downtime_scheduled_start'  => 'UNIX_TIMESTAMP(sd.scheduled_start_time)',
            'downtime_start'            => 'UNIX_TIMESTAMP(CASE WHEN UNIX_TIMESTAMP(sd.trigger_time) > 0 then sd.trigger_time ELSE sd.scheduled_start_time END)',
            'downtime_triggered_by_id'  => 'sd.triggered_by_id',
            'host'                      => 'so.name1 COLLATE latin1_general_ci',
            'host_name'                 => 'so.name1',
            'object_type'               => '(\'service\')',
            'service'                   => 'so.name2 COLLATE latin1_general_ci',
            'service_description'       => 'so.name2',
            'service_host'              => 'so.name1 COLLATE latin1_general_ci',
            'service_host_name'         => 'so.name1'
        ),
        'hostgroups' => array(
            'hostgroup'         => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_alias'   => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'    => 'hgo.name1'
        ),
        'hosts' => array(
            'host_alias'            => 'h.alias',
            'host_display_name'     => 'h.display_name COLLATE latin1_general_ci'
        ),
        'hoststatus' => array(
            'host_state' => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END'
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
            'service_display_name' => 's.display_name COLLATE latin1_general_ci'
        ),
        'servicestatus' => array(
            'service_state' => 'CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END'
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('sd' => $this->prefix . 'scheduleddowntime'),
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'sd.object_id = so.object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
        $this->joinedVirtualTables['downtimes'] = true;
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
        $this->select->join(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = s.host_object_id',
            array()
        );
    }

    /**
     * Join host status
     */
    protected function joinHoststatus()
    {
        $this->requireVirtualTable('services');
        $this->select->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'hs.host_object_id = s.host_object_id',
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
            'i.instance_id = sd.instance_id',
            array()
        );
    }

    /**
     * Join service groups
     */
    protected function joinServicegroups()
    {
        $this->select->joinLeft(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = so.object_id',
            array()
        )->joinLeft(
            array('sg' => $this->prefix . 'servicegroups'),
            'sgm.servicegroup_id = sg.' . $this->servicegroup_id,
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
        $this->select->join(
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
        $this->select->join(
            array('ss' => $this->prefix . 'servicestatus'),
            'ss.service_object_id = so.object_id',
            array()
        );
    }
}
