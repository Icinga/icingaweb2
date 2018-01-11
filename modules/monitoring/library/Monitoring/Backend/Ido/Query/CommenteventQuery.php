<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for host and service comment entry and deletion events
 */
class CommenteventQuery extends IdoQuery
{
    protected $columnMap = array(
        'commentevent' => array(
            'commentevent_id'               => 'ch.commenthistory_id',
            'commentevent_entry_type'       => "(CASE ch.entry_type WHEN 1 THEN 'comment' WHEN 2 THEN 'downtime' WHEN 3 THEN 'flapping' WHEN 4 THEN 'ack' ELSE NULL END)",
            'commentevent_comment_time'     => 'UNIX_TIMESTAMP(ch.comment_time)',
            'commentevent_author_name'      => 'ch.author_name',
            'commentevent_comment_data'     => 'ch.comment_data',
            'commentevent_is_persistent'    => 'ch.is_persistent',
            'commentevent_comment_source'   => "(CASE ch.comment_source WHEN 0 THEN 'icinga' WHEN 1 THEN 'user' ELSE NULL END)",
            'commentevent_expires'          => 'ch.expires',
            'commentevent_expiration_time'  => 'UNIX_TIMESTAMP(ch.expiration_time)',
            'commentevent_deletion_time'    => 'UNIX_TIMESTAMP(ch.deletion_time)'
        ),
        'object' => array(
            'host_name'             => 'o.name1',
            'service_description'   => 'o.name2'
        )
    );

    protected function joinBaseTables()
    {
        $this->select()
            ->from(array('ch' => $this->prefix . 'commenthistory'), array())
            ->join(array('o' => $this->prefix . 'objects'), 'ch.object_id = o.object_id', array());

        $this->joinedVirtualTables['commentevent'] = true;
        $this->joinedVirtualTables['object'] = true;
    }
}
