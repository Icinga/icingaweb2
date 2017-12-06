<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Notificationevent extends DataView
{
    public function getColumns()
    {
        return array(
            'notificationevent_id',
            'notificationevent_reason',
            'notificationevent_start_time',
            'notificationevent_end_time',
            'notificationevent_state',
            'notificationevent_output',
            'notificationevent_long_output',
            'notificationevent_escalated',
            'notificationevent_contacts_notified',
            'host_name',
            'service_description'
        );
    }

    public function getStaticFilterColumns()
    {
        return array('notificationevent_id');
    }
}
