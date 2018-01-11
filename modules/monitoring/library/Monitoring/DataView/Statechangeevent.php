<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Statechangeevent extends DataView
{
    public function getColumns()
    {
        return array(
            'statechangeevent_id',
            'statechangeevent_state_time',
            'statechangeevent_state_change',
            'statechangeevent_state',
            'statechangeevent_state_type',
            'statechangeevent_current_check_attempt',
            'statechangeevent_max_check_attempts',
            'statechangeevent_last_state',
            'statechangeevent_last_hard_state',
            'statechangeevent_output',
            'statechangeevent_long_output',
            'host_name',
            'service_description'
        );
    }

    public function getStaticFilterColumns()
    {
        return array('statechangeevent_id');
    }
}
