<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\DataView;

/**
 * Represent customvar view
 */
class Customvar extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'varname',
            'varvalue',
            'object_type'
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
            'varname' => array(
                'varname'  => self::SORT_ASC,
                'varvalue' => self::SORT_ASC,
            )
        );
    }
}
