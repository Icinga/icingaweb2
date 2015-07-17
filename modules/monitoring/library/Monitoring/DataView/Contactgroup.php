<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Contactgroup extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function isValidFilterTarget($column)
    {
        if ($column[0] === '_' && preg_match('/^_(?:host|service)_/', $column)) {
            return true;
        }

        return parent::isValidFilterTarget($column);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'contactgroup_name',
            'contactgroup_alias',
            'contact_object_id',
            'contact_id',
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
            'contact_notify_host_timeperiod',
            'contact_notify_service_timeperiod'
        );
    }

    /**
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
     */
    public function getFilterColumns()
    {
        return array(
            'contactgroup', 'contact',
            'host', 'host_name', 'host_display_name', 'host_alias',
            'hostgroup', 'hostgroup_alias', 'hostgroup_name',
            'service', 'service_description', 'service_display_name',
            'servicegroup', 'servicegroup_alias', 'servicegroup_name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchColumns()
    {
        return array('contactgroup', 'contactgroup_alias', 'contact', 'contact_alias', 'contact_email');
    }
}
