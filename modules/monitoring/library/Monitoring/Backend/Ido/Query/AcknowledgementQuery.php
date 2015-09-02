<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Select;

/**
 * Query for active acknowledgements
 */
class AcknowledgementQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'acknowledgements' => array(
            'acknowledgement_id'   => 'a.acknowledgement_id',
            'instance_id'          => 'a.instance_id',
            'entry_time'           => 'UNIX_TIMESTAMP(a.entry_time)',
            'object_id'            => 'a.object_id',
            'state'                => 'a.state',
            'author_name'          => 'a.author_name',
            'comment_data'         => 'a.comment_data',
            'is_sticky'            => 'a.is_sticky',
            'persistent_comment'   => 'a.persistent_comment',
            'acknowledgement_id'   => 'a.acknowledgement_id',
            'notify_contacts'      => 'a.notify_contacts',
            'end_time'             => 'UNIX_TIMESTAMP(a.end_time)',
            'endpoint_object_id'   => 'a.endpoint_object_id'
        ),
        'objects' => array(
            'acknowledgement_is_service' => '(CASE WHEN o.objecttype_id = 2 THEN 1 ELSE 0 END)',
            'host'                       => 'o.name1',
            'service'                    => 'o.name2'
        )
    );

    /**
     * @var Zend_Db_Select
     */
    protected $acknowledgementQuery;

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->acknowledgementQuery = $this->db->select();
        $this->select->from(
            array('o' => $this->prefix . 'objects'),
            array()
        );
        $this->select->joinLeft(
            array('hs' => $this->prefix . 'hoststatus'),
            'hs.host_object_id = o.object_id AND o.is_active = 1',
            array()
        );
        $this->select->joinLeft(
            array('ss' => $this->prefix . 'servicestatus'),
            'ss.service_object_id = o.object_id AND o.is_active = 1',
            array()
        );
        $ackTable = $this->prefix . 'acknowledgements';
        $subQuery = '(SELECT MAX(acknowledgement_id) FROM ' . $ackTable . ' WHERE object_id = a.object_id)';
        $this->select->join(
            array('a' => $ackTable),
            'o.object_id = a.object_id ' .
            'AND ((o.objecttype_id = 2 AND ss.problem_has_been_acknowledged = 1 AND ss.acknowledgement_type = 2) ' .
            '  OR (o.objecttype_id = 1 AND hs.problem_has_been_acknowledged = 1 AND hs.acknowledgement_type = 2)) ' .
            'AND o.is_active = 1 AND a.acknowledgement_id = ' . $subQuery,
            array()
        );

        $this->joinedVirtualTables['objects'] = true;
        $this->joinedVirtualTables['acknowledgements'] = true;
        $this->joinedVirtualTables['hoststatus'] = true;
        $this->joinedVirtualTables['servicestatus'] = true;
    }
}
