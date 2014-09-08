<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Handling downtime queries
 */
class DowntimeQuery extends IdoQuery
{
    /**
     * Column map
     * @var array
     */
    protected $columnMap = array(
        'downtime' => array(
            'downtime_author'          => 'sd.author_name',
            'downtime_comment'         => 'sd.comment_data',
            'downtime_entry_time'      => 'UNIX_TIMESTAMP(sd.entry_time)',
            'downtime_is_fixed'        => 'sd.is_fixed',
            'downtime_is_flexible'     => 'CASE WHEN sd.is_fixed = 0 THEN 1 ELSE 0 END',
            'downtime_triggered_by_id' => 'sd.triggered_by_id',
            'downtime_scheduled_start' => 'UNIX_TIMESTAMP(sd.scheduled_start_time)',
            'downtime_scheduled_end'   => 'UNIX_TIMESTAMP(sd.scheduled_end_time)',
            'downtime_start'           => "UNIX_TIMESTAMP(CASE WHEN UNIX_TIMESTAMP(sd.trigger_time) > 0 then sd.trigger_time ELSE sd.scheduled_start_time END)",
            'downtime_end'             => 'CASE WHEN sd.is_fixed > 0 THEN UNIX_TIMESTAMP(sd.scheduled_end_time) ELSE UNIX_TIMESTAMP(sd.trigger_time) + sd.duration END',
            'downtime_duration'        => 'sd.duration',
            'downtime_is_in_effect'    => 'sd.is_in_effect',
            'downtime_internal_id'     => 'sd.internal_downtime_id',
            'downtime_host'            => 'CASE WHEN ho.name1 IS NULL THEN so.name1 ELSE ho.name1 END COLLATE latin1_general_ci',
            'downtime_service'         => 'so.name2 COLLATE latin1_general_ci',
            'downtime_objecttype'      => "CASE WHEN ho.object_id IS NOT NULL THEN 'host' ELSE CASE WHEN so.object_id IS NOT NULL THEN 'service' ELSE NULL END END",
            'downtime_host_state'      => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END',
            'downtime_service_state'   => 'CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END'
        ),
    );

    /**
     * Join with scheduleddowntime
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
        $this->select->joinLeft(
            array('hs' => $this->prefix . 'hoststatus'),
            'ho.object_id = hs.host_object_id',
            array()
        );
        $this->select->joinLeft(
            array('ss' => $this->prefix . 'servicestatus'),
            'so.object_id = ss.service_object_id',
            array()
        );
        $this->joinedVirtualTables = array('downtime' => true);
    }
}
