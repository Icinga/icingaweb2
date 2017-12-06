<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host and service flapping events
 */
class FlappingeventQuery extends IdoQuery
{
    protected $columnMap = array(
        'flappingevent' => array(
            'flappingevent_id'                      => 'fh.flappinghistory_id',
            'flappingevent_event_time'              => 'UNIX_TIMESTAMP(fh.event_time)',
            'flappingevent_event_type'              => "(CASE fh.event_type WHEN 1000 THEN 'flapping' WHEN 1001 THEN 'flapping_deleted' ELSE NULL END)",
            'flappingevent_reason_type'             => "(CASE fh.reason_type WHEN 1 THEN 'stopped' WHEN 2 THEN 'disabled' ELSE NULL END)",
            'flappingevent_percent_state_change'    => 'fh.percent_state_change',
            'flappingevent_low_threshold'           => 'fh.low_threshold',
            'flappingevent_high_threshold'          => 'fh.high_threshold'
        ),
        'object' => array(
            'host_name'             => 'o.name1',
            'service_description'   => 'o.name2'
        )
    );

    protected function joinBaseTables()
    {
        $this->select()
            ->from(array('fh' => $this->prefix . 'flappinghistory'), array())
            ->join(array('o' => $this->prefix . 'objects'), 'fh.object_id = o.object_id', array());

        $this->joinedVirtualTables['flappingevent'] = true;
        $this->joinedVirtualTables['object'] = true;
    }
}
