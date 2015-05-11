<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Describes the data needed by the 'Contact' DataView
 */
class Contact extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'contact_id',
            'contact_name',
            'contact_alias',
            'contact_email',
            'contact_pager',
            'contact_notify_hosts',
            'contact_notify_services',
            'contact_has_host_notfications',
            'contact_has_service_notfications',
            'contact_can_submit_commands',
            'contact_notify_service_recovery',
            'contact_notify_service_warning',
            'contact_notify_service_critical',
            'contact_notify_service_unknown',
            'contact_notify_service_flapping',
            'contact_notify_service_downtime',
            'contact_notify_host_recovery',
            'contact_notify_host_down',
            'contact_notify_host_unreachable',
            'contact_notify_host_flapping',
            'contact_notify_host_downtime',
            'contact_object_id',
            'host_object_id',
            'host_name',
            'service_object_id',
            'service_host_name',
            'service_description',
            'contact_notify_host_timeperiod',
            'contact_notify_service_timeperiod'
        );
    }

    /**
     * Retrieve default sorting rules for particular columns. These involve sort order and potential additional to sort
     *
     * @return array
     */
    public function getSortRules()
    {
        return array(
            'contact_alias' => array(
                'order' => self::SORT_DESC
            )
        );
    }

    public function getFilterColumns()
    {
        return array('contact', 'alias', 'email', 'host', 'service', 'service_host');
    }
}
