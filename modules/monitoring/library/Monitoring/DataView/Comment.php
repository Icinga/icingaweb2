<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Monitoring\DataView;

/**
 * View representation for comments
 */
class Comment extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'comment_objecttype',
            'comment_internal_id',
            'comment_data',
            'comment_author',
            'comment_timestamp',
            'comment_type',
            'comment_is_persistent',
            'comment_expiration',
            'comment_host',
            'comment_service',
            'host',
            'service',
            'host_display_name',
            'service_display_name'
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
            'comment_timestamp' => array(
                'order' => self::SORT_DESC
            ),
            'host_display_name' => array(
                'columns' => array(
                    'host_display_name',
                    'service_display_name'
                ),
                'order' => self::SORT_ASC
            ),
            'service_display_name' => array(
                'columns' => array(
                    'service_display_name',
                    'host_display_name'
                ),
                'order' => self::SORT_ASC
            )
        );
    }
}
