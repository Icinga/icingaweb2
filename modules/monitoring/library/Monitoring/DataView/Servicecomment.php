<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Service comment view
 */
class Servicecomment extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'comment_author',
            'comment_author_name',
            'comment_data',
            'comment_expiration',
            'comment_internal_id',
            'comment_is_persistent',
            'comment_name',
            'comment_timestamp',
            'comment_type',
            'host_display_name',
            'host_name',
            'object_type',
            'service_description',
            'service_display_name',
            'service_host_name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticFilterColumns()
    {
        return array(
            'host', 'host_alias',
            'hostgroup', 'hostgroup_alias', 'hostgroup_name',
            'instance_name',
            'service',
            'servicegroup', 'servicegroup_alias', 'servicegroup_name'
        );
    }
}
