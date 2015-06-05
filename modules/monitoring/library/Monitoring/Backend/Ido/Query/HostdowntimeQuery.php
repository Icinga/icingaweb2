<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host downtimes
 */
class HostdowntimeQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

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
            'host'                      => 'ho.name1 COLLATE latin1_general_ci',
            'host_name'                 => 'ho.name1',
            'object_type'               => '(\'host\')'
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
        'servicegroups' => array(
            'servicegroup'          => 'sgo.name1 COLLATE latin1_general_ci',
            'servicegroup_name'     => 'sgo.name1',
            'servicegroup_alias'    => 'sg.alias COLLATE latin1_general_ci'
        ),
        'services' => array(
            'service'                => 'so.name2 COLLATE latin1_general_ci',
            'service_description'    => 'so.name2',
            'service_display_name'   => 's.display_name COLLATE latin1_general_ci',
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
            array('ho' => $this->prefix . 'objects'),
            'sd.object_id = ho.object_id',
            array()
        )->where(
            'ho.is_active = ?',
            1
        )->where(
            'ho.objecttype_id = ?',
            1
        );
        $this->joinedVirtualTables['downtimes'] = true;
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->select->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = ho.object_id',
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
        $this->group('ho.name1');
    }

    /**
     * Join hosts
     */
    protected function joinHosts()
    {
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
        $this->select->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'hs.host_object_id = ho.object_id',
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
            'sgm.servicegroup_id = sg.' . $this->servicegroup_id,
            array()
        )->join(
            array('sgo' => $this->prefix . 'objects'),
            'sgo.object_id = sg.servicegroup_object_id',
            array()
        )->where(
            'sgo.is_active = ?',
            1
        )
        ->where(
            'sgo.objecttype_id = ?',
            4
        );
        $this->group('ho.name1');
    }

    /**
     * Join services
     */
    protected function joinServices()
    {
        $this->select->join(
            array('s' => $this->prefix . 'services'),
            's.host_object_id = h.host_object_id',
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.object_id = s.service_object_id AND so.is_active = 1',
            array()
        )->where(
            'so.is_active = ?',
            1
        )
        ->where(
            'so.objecttype_id = ?',
            2
        );
        $this->group('ho.name1');
    }
}
