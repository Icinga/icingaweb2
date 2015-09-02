<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

/**
 * Host and service comments view
 */
class Acknowledgement extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array(
            'acknowledgement_id',
            'instance_id',
            'entry_time',
            'object_id',
            'state',
            'author_name',
            'comment_data',
            'is_sticky',
            'persistent_comment',
            'acknowledgement_id',
            'notify_contacts',
            'end_time',
            'acknowledgement_is_service'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticFilterColumns()
    {
        return array(
            'acknowledgement_id',
            'entry_time',
            'state',
            'author_name',
            'comment_data',
            'is_sticky',
            'persistent_comment',
            'acknowledgement_id',
            'notify_contacts',
            'entry_time',
            'acknowledgement_is_service'
        );
    }
}
