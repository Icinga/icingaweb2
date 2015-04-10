<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Describes the data needed by the Contactgroup DataView
 */
class Contactgroup extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'contactgroup_name',
            'contactgroup_alias',
            'contact_name',
            'contact_alias',
            'contact_email',
            'contact_pager',
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
            'host_name',
            'service_description',
            'service_host_name'
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
            'contactgroup_name' => array(
                'order' => self::SORT_ASC
            ),
            'contactgroup_alias' => array(
                'order' => self::SORT_ASC
            )
        );
    }

    public function getFilterColumns()
    {
        return array('contactgroup', 'contact', 'host', 'service', 'service_host');
    }
}
