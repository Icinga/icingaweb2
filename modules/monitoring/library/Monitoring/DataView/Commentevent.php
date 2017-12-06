<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class Commentevent extends DataView
{
    public function getColumns()
    {
        return array(
            'commentevent_id',
            'commentevent_entry_type',
            'commentevent_comment_time',
            'commentevent_author_name',
            'commentevent_comment_data',
            'commentevent_is_persistent',
            'commentevent_comment_source',
            'commentevent_expires',
            'commentevent_expiration_time',
            'commentevent_deletion_time',
            'host_name',
            'service_description'
        );
    }

    public function getStaticFilterColumns()
    {
        return array('commentevent_id');
    }
}
