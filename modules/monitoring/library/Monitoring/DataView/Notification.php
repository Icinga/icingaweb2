<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
            'host_name',
            'service_description',
            'notification_type',
            'notification_reason',
            'notification_start_time',
            'notification_contact',
            'notification_information',
            'notification_command'
        );
    }

    public function getSortRules()
    {
        return array(
            'notification_start_time' => array(
                'order' => self::SORT_DESC
            )
        );
    }
}
