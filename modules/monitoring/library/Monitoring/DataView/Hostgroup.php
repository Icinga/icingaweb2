<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * View for hostgroups
 */
class Hostgroup extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'hostgroup_name',
            'hostgroup_alias',
            'hostgroup_id',
            'host_name'
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
            'hostgroup_name' => array(
                'order' => self::SORT_ASC
            ),
            'hostgroup_alias' => array(
                'order' => self::SORT_ASC
            )
        );
    }

    public function getFilterColumns()
    {
        return array('hostgroup', 'host');
    }
}
