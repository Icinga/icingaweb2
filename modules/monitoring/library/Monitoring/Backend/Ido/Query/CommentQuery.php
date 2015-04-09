<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
            'comment_author_name'   => 'cm.author_name',
            'comment_timestamp'     => 'UNIX_TIMESTAMP(cm.comment_time)',
            'comment_type'          => "CASE cm.entry_type WHEN 1 THEN 'comment' WHEN 2 THEN 'downtime' WHEN 3 THEN 'flapping' WHEN 4 THEN 'ack' END",
            'comment_is_persistent' => 'cm.is_persistent',
            'comment_expiration'    => 'CASE cm.expires WHEN 1 THEN UNIX_TIMESTAMP(cm.expiration_time) ELSE NULL END',
            'comment_objecttype'    => "CASE WHEN ho.object_id IS NOT NULL THEN 'host' ELSE CASE WHEN so.object_id IS NOT NULL THEN 'service' ELSE NULL END END",
            'host'                  => 'CASE WHEN ho.name1 IS NULL THEN so.name1 ELSE ho.name1 END COLLATE latin1_general_ci',
            'host_name'             => 'CASE WHEN ho.name1 IS NULL THEN so.name1 ELSE ho.name1 END',
            'service'               => 'so.name2 COLLATE latin1_general_ci',
            'service_description'   => 'so.name2',
            'service_host'          => 'so.name1 COLLATE latin1_general_ci',
            'service_host_name'     => 'so.name1'
        ),
        'hosts' => array(
            'host_display_name'     => 'CASE WHEN sh.display_name IS NOT NULL THEN sh.display_name ELSE h.display_name END'
        ),
        'services' => array(
            'service_display_name'  => 's.display_name'
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

    protected function joinHosts()
    {
        $this->select->joinLeft(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = ho.object_id',
            array()
        );
        return $this;
    }

    protected function joinServices()
    {
        $this->select->joinLeft(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = so.object_id',
            array()
        );
        $this->select->joinLeft(
            array('sh' => $this->prefix . 'hosts'),
            'sh.host_object_id = s.host_object_id',
            array()
        );
        return $this;
    }
}
