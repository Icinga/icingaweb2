<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Notification extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'instance_name',
            'notification_state',
            'notification_start_time',
            'notification_contact_name',
            'notification_output',
            'notification_object_id',
            'contact_object_id',
            'acknowledgement_entry_time',
            'acknowledgement_author_name',
            'acknowledgement_comment_data',
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
    public function getSortRules()
    {
        return array(
            'notification_start_time' => array(
                'order' => self::SORT_DESC,
                'title' => 'Notification Start'
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

    /**
     * {@inheritdoc}
     */
    public function getStaticFilterColumns()
    {
        return array(
            'contact',
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
        return array('host_display_name', 'service_display_name');
    }
}
