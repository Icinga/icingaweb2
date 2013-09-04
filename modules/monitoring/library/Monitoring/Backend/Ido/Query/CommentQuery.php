<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class CommentQuery extends AbstractQuery
{
    protected $columnMap = array(
        'comments' => array(
            'comment_data'      => 'cm.comment_data',
            'comment_author'    => 'cm.author_name',
            //'comment_timestamp' => 'UNIX_TIMESTAMP(cm.entry_time)',
            'comment_timestamp' => 'UNIX_TIMESTAMP(cm.comment_time)',
            'comment_type'      => "CASE cm.entry_type WHEN 1 THEN 'comment' WHEN 2 THEN 'downtime' WHEN 3 THEN 'flapping' WHEN 4 THEN 'ack' END",
        ),
        'hosts' => array(
            'host_name' => 'ho.name1',
        ),
        'services' => array(
            'service_host_name'   => 'so.name1 COLLATE latin1_general_ci',
            'service_description' => 'so.name2 COLLATE latin1_general_ci',
        )
    );

    protected function joinBaseTables()
    {
        $this->baseQuery = $this->db->select()->from(
            array('cm' => $this->prefix . 'comments'),
            array()
        );

        $this->joinedVirtualTables = array('comments' => true);
    }

    protected function joinHosts()
    {
        $this->baseQuery->join(
            array('ho' => $this->prefix . 'objects'),
            'cm.object_id = ho.' . $this->object_id . ' AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );
    }

    protected function joinServices()
    {
        $this->baseQuery->join(
            array('so' => $this->prefix . 'objects'),
            'cm.object_id = so.' . $this->object_id . ' AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
    }
}
