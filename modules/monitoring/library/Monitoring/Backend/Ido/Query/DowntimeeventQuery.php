<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host and service downtime events
 */
class DowntimeeventQuery extends IdoQuery
{
    protected $columnMap = array(
        'downtimeevent' => array(
            'downtimeevent_id'                      => 'dth.downtimehistory_id',
            'downtimeevent_entry_time'              => 'UNIX_TIMESTAMP(dth.entry_time)',
            'downtimeevent_author_name'             => 'dth.author_name',
            'downtimeevent_comment_data'            => 'dth.comment_data',
            'downtimeevent_is_fixed'                => 'dth.is_fixed',
            'downtimeevent_scheduled_start_time'    => 'UNIX_TIMESTAMP(dth.scheduled_start_time)',
            'downtimeevent_scheduled_end_time'      => 'UNIX_TIMESTAMP(dth.scheduled_end_time)',
            'downtimeevent_was_started'             => 'dth.was_started',
            'downtimeevent_actual_start_time'       => 'UNIX_TIMESTAMP(dth.actual_start_time)',
            'downtimeevent_actual_end_time'         => 'UNIX_TIMESTAMP(dth.actual_end_time)',
            'downtimeevent_was_cancelled'           => 'dth.was_cancelled',
            'downtimeevent_is_in_effect'            => 'dth.is_in_effect',
            'downtimeevent_trigger_time'            => 'UNIX_TIMESTAMP(dth.trigger_time)'
        ),
        'object' => array(
            'host_name'             => 'o.name1',
            'service_description'   => 'o.name2'
        )
    );

    protected function joinBaseTables()
    {
        $this->select()
            ->from(array('dth' => $this->prefix . 'downtimehistory'), array())
            ->join(array('o' => $this->prefix . 'objects'), 'dth.object_id = o.object_id', array());

        $this->joinedVirtualTables['downtimeevent'] = true;
        $this->joinedVirtualTables['object'] = true;
    }
}
