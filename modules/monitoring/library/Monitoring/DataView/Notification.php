<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Monitoring\DataView;

class Notification extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'host',
            'service',
            'notification_state',
            'notification_start_time',
            'notification_contact',
            'notification_output',
            'notification_command',
            'host_display_name',
            'service_display_name'
        );
    }

    public function getSortRules()
    {
        return array(
            'notification_start_time' => array(
                'order' => self::SORT_DESC,
                'title' => 'Notification Start'
            )
        );
    }
}
