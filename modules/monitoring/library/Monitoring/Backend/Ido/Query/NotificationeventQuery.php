<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host and service notification events
 */
class NotificationeventQuery extends IdoQuery
{
    protected $columnMap = array(
        'notificationevent' => array(
            'notificationevent_id'                  => 'n.notification_id',
            'notificationevent_reason' => <<<EOF
(CASE n.notification_reason
    WHEN 0 THEN 'normal_notification'
    WHEN 1 THEN 'ack'
    WHEN 2 THEN 'flapping_started'
    WHEN 3 THEN 'flapping_stopped'
    WHEN 4 THEN 'flapping_disabled'
    WHEN 5 THEN 'dt_start'
    WHEN 6 THEN 'dt_end'
    WHEN 7 THEN 'dt_cancel'
    WHEN 99 THEN 'custom_notification'
    ELSE NULL
END)
EOF
            ,
            'notificationevent_start_time'          => 'UNIX_TIMESTAMP(n.start_time)',
            'notificationevent_end_time'            => 'UNIX_TIMESTAMP(n.end_time)',
            'notificationevent_state'               => 'n.state',
            'notificationevent_output'              => 'n.output',
            'notificationevent_long_output'         => 'n.long_output',
            'notificationevent_escalated'           => 'n.escalated',
            'notificationevent_contacts_notified'   => 'n.contacts_notified'
        ),
        'object' => array(
            'host_name'             => 'o.name1',
            'service_description'   => 'o.name2'
        )
    );

    protected function joinBaseTables()
    {
        $this->select()
            ->from(array('n' => $this->prefix . 'notifications'), array())
            ->join(array('o' => $this->prefix . 'objects'), 'n.object_id = o.object_id', array());

        $this->joinedVirtualTables['notificationevent'] = true;
        $this->joinedVirtualTables['object'] = true;
    }
}
