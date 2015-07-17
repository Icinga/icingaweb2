<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class EventHistory extends DataView
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
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'cnt_notification',
            'cnt_hard_state',
            'cnt_soft_state',
            'cnt_downtime_start',
            'cnt_downtime_end',
            'host_name',
            'host_display_name',
            'service_description',
            'service_display_name',
            'object_type',
            'timestamp',
            'state',
            'output',
            'type'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSortRules()
    {
        return array(
            'timestamp' => array(
                'columns'   => array('timestamp'),
                'order'     => 'DESC'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterColumns()
    {
        return array(
            'host', 'host_alias',
            'hostgroup', 'hostgroup_alias', 'hostgroup_name',
            'service',
            'servicegroup', 'servicegroup_alias', 'servicegroup_name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchColumns()
    {
        return array('host', 'host_display_name', 'service', 'service_display_name', 'type');
    }
}
