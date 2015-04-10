<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Servicegroup extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'servicegroup_name',
            'servicegroup_alias',
            'host_name',
            'service_host_name',
            'service_description'
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
            'servicegroup_name' => array(
                'order' => self::SORT_ASC
            ),
            'servicegroup_alias' => array(
                'order' => self::SORT_ASC
            )
        );
    }

    public function getFilterColumns()
    {
        return array('servicegroup', 'host', 'service');
    }
}
