<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host and service state change events
 */
class StatechangeeventQuery extends IdoQuery
{
    protected $columnMap = array(
        'statechangeevent' => array(
            'statechangeevent_id'                       => 'sh.statehistory_id',
            'statechangeevent_state_time'               => 'UNIX_TIMESTAMP(sh.state_time)',
            'statechangeevent_state_change'             => 'sh.state_change',
            'statechangeevent_state'                    => 'sh.state',
            'statechangeevent_state_type'               => "(CASE sh.state_type WHEN 0 THEN 'soft_state' WHEN 1 THEN 'hard_state' ELSE NULL END)",
            'statechangeevent_current_check_attempt'    => 'sh.current_check_attempt',
            'statechangeevent_max_check_attempts'       => 'sh.max_check_attempts',
            'statechangeevent_last_state'               => 'sh.last_state',
            'statechangeevent_last_hard_state'          => 'sh.last_hard_state',
            'statechangeevent_output'                   => 'sh.output',
            'statechangeevent_long_output'              => 'sh.long_output'
        ),
        'object' => array(
            'host_name'             => 'o.name1',
            'service_description'   => 'o.name2'
        )
    );

    protected function joinBaseTables()
    {
        $this->select()
            ->from(array('sh' => $this->prefix . 'statehistory'), array())
            ->join(array('o' => $this->prefix . 'objects'), 'sh.object_id = o.object_id', array());

        $this->joinedVirtualTables['statechangeevent'] = true;
        $this->joinedVirtualTables['object'] = true;
    }
}
