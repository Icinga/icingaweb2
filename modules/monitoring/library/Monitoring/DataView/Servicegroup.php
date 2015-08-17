<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Service group view */
class Servicegroup extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'servicegroup_alias',
            'servicegroup_name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterColumns()
    {
        return array(
            'host', 'host_alias', 'host_display_name', 'host_name',
            'hostgroup', 'hostgroup_alias', 'hostgroup_name',
            'service', 'service_description', 'service_display_name',
            'servicegroup'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isValidFilterTarget($column)
    {
        if ($column[0] === '_'
            && preg_match('/^_(?:host|service)_/', $column)
        ) {
            return true;
        } else {
            return parent::isValidFilterTarget($column);
        }
    }
}
