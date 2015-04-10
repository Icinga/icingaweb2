<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host and service downtimes
 */
class DowntimeQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'downtime' => array(
            'downtime_author_name'      => 'sd.author_name',
            'author'                    => 'sd.author_name COLLATE latin1_general_ci',
            'downtime_comment'          => 'sd.comment_data',
            'downtime_entry_time'       => 'UNIX_TIMESTAMP(sd.entry_time)',
            'downtime_is_fixed'         => 'sd.is_fixed',
            'downtime_is_flexible'      => 'CASE WHEN sd.is_fixed = 0 THEN 1 ELSE 0 END',
            'downtime_triggered_by_id'  => 'sd.triggered_by_id',
            'downtime_scheduled_start'  => 'UNIX_TIMESTAMP(sd.scheduled_start_time)',
            'downtime_scheduled_end'    => 'UNIX_TIMESTAMP(sd.scheduled_end_time)',
            'downtime_start'            => "UNIX_TIMESTAMP(CASE WHEN UNIX_TIMESTAMP(sd.trigger_time) > 0 then sd.trigger_time ELSE sd.scheduled_start_time END)",
            'downtime_end'              => 'CASE WHEN sd.is_fixed > 0 THEN UNIX_TIMESTAMP(sd.scheduled_end_time) ELSE UNIX_TIMESTAMP(sd.trigger_time) + sd.duration END',
            'downtime_duration'         => 'sd.duration',
            'downtime_is_in_effect'     => 'sd.is_in_effect',
            'downtime_internal_id'      => 'sd.internal_downtime_id',
            'downtime_objecttype'       => "CASE WHEN ho.object_id IS NULL THEN 'service' ELSE 'host' END",
            'host'                      => 'CASE WHEN ho.name1 IS NULL THEN so.name1 ELSE ho.name1 END COLLATE latin1_general_ci',
            'host_name'                 => 'CASE WHEN ho.name1 IS NULL THEN so.name1 ELSE ho.name1 END',
            'service'                   => 'so.name2 COLLATE latin1_general_ci',
            'service_description'       => 'so.name2',
            'service_host'              => 'so.name1 COLLATE latin1_general_ci',
            'service_host_name'         => 'so.name1'
        ),
        'hosts' => array(
            'host_display_name'         => 'CASE WHEN h.display_name IS NULL THEN sh.display_name ELSE h.display_name END'
        ),
        'hoststatus' => array(
            'downtime_host_state'       => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END'
        ),
        'services' => array(
            'service_display_name'      => 's.display_name'
        ),
        'servicestatus' => array(
            'downtime_service_state'    => 'CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END'
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
        );
        $this->select->joinLeft(
            array('ho' => $this->prefix . 'objects'),
            'sd.object_id = ho.object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );
        $this->select->joinLeft(
            array('so' => $this->prefix . 'objects'),
            'sd.object_id = so.object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
        $this->joinedVirtualTables = array('downtime' => true);
    }

    /**
     * Join downtimes' hosts
     *
     * @return $this
     */
    protected function joinHosts()
    {
        $this->select->joinLeft(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = ho.object_id',
            array()
        );
        return $this;
    }

    /**
     * Join downtimes' hosts' status
     *
     * @return $this
     */
    protected function joinHoststatus()
    {
        $this->select->joinLeft(
            array('hs' => $this->prefix . 'hoststatus'),
            'ho.object_id = hs.host_object_id',
            array()
        );
        return $this;
    }

    /**
     * Join downtimes' services
     *
     * @return $this
     */
    protected function joinServices()
    {
        $this->select->joinLeft(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = so.object_id',
            array()
        );
        $this->select->joinLeft(
            array('sh' => $this->prefix . 'hosts'),
            'sh.host_object_id = s.host_object_id',
            array()
        );
        return $this;
    }

    /**
     * Join downtimes' services' status
     *
     * @return $this
     */
    protected function joinServicestatus()
    {
        $this->select->joinLeft(
            array('ss' => $this->prefix . 'servicestatus'),
            'so.object_id = ss.service_object_id',
            array()
        );
        return $this;
    }
}
