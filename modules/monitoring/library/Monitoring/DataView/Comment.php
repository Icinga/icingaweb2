<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
            'comment_host' => array(
                'columns' => array(
                    'comment_host',
                    'comment_service'
                ),
                'order' => self::SORT_ASC
            ),
        );
    }
}
