<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Downtimeevent extends DataView
{
    public function getColumns()
    {
        return array(
            'downtimeevent_id',
            'downtimeevent_entry_time',
            'downtimeevent_author_name',
            'downtimeevent_comment_data',
            'downtimeevent_is_fixed',
            'downtimeevent_scheduled_start_time',
            'downtimeevent_scheduled_end_time',
            'downtimeevent_was_started',
            'downtimeevent_actual_start_time',
            'downtimeevent_actual_end_time',
            'downtimeevent_was_cancelled',
            'downtimeevent_is_in_effect',
            'downtimeevent_trigger_time',
            'host_name',
            'service_description'
        );
    }

    public function getStaticFilterColumns()
    {
        return array('downtimeevent_id');
    }
}
