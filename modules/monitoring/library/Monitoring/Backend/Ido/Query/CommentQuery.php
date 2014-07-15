<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query map for comments
 */
class CommentQuery extends IdoQuery
{
    protected $columnMap = array(
        'comments' => array(
            'comment_internal_id'   => 'cm.internal_comment_id',
            'comment_data'          => 'cm.comment_data',
            'comment_author'        => 'cm.author_name COLLATE latin1_general_ci',
            'comment_timestamp'     => 'UNIX_TIMESTAMP(cm.comment_time)',
            'comment_type'          => "CASE cm.entry_type WHEN 1 THEN 'comment' WHEN 2 THEN 'downtime' WHEN 3 THEN 'flapping' WHEN 4 THEN 'ack' END",
            'comment_is_persistent' => 'cm.is_persistent',
            'comment_expiration'    => 'CASE cm.expires WHEN 1 THEN UNIX_TIMESTAMP(cm.expiration_time) ELSE NULL END',
            'comment_host'          => 'CASE WHEN ho.name1 IS NULL THEN so.name1 ELSE ho.name1 END COLLATE latin1_general_ci',
            'host'          => 'CASE WHEN ho.name1 IS NULL THEN so.name1 ELSE ho.name1 END COLLATE latin1_general_ci',
            'comment_service'       => 'so.name2 COLLATE latin1_general_ci',
            'service'       => 'so.name2 COLLATE latin1_general_ci',
            'comment_objecttype'    => "CASE WHEN ho.object_id IS NOT NULL THEN 'host' ELSE CASE WHEN so.object_id IS NOT NULL THEN 'service' ELSE NULL END END",
        )
    );

    protected function joinBaseTables()
    {
        $this->select->from(
            array('cm' => $this->prefix . 'comments'),
            array()
        );
        $this->select->joinLeft(
            array('ho' => $this->prefix . 'objects'),
            'cm.object_id = ho.object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        );
        $this->select->joinLeft(
            array('so' => $this->prefix . 'objects'),
            'cm.object_id = so.object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        );
        $this->joinedVirtualTables = array('comments' => true);
    }
}
