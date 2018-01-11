<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Flappingevent extends DataView
{
    public function getColumns()
    {
        return array(
            'flappingevent_id',
            'flappingevent_event_time',
            'flappingevent_event_type',
            'flappingevent_reason_type',
            'flappingevent_percent_state_change',
            'flappingevent_low_threshold',
            'flappingevent_high_threshold',
            'host_name',
            'service_description'
        );
    }

    public function getStaticFilterColumns()
    {
        return array('flappingevent_id');
    }
}
