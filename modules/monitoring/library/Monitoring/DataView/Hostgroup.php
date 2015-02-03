<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}


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
            'host',
            'hostgroup_name',
            'hostgroup_alias'
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
            )
        );
    }
}
