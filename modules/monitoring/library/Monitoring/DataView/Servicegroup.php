<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}


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
            'service',
            'host',
            'servicegroup_name',
            'servicegroup_alias'
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
            )
        );
    }
}
