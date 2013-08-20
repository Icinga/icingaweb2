<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Icinga\Exception\ProgrammingError;

class EventHistoryQuery extends AbstractQuery
{
    protected $subQueries = array();

    protected $columnMap = array(
        'eventhistory' => array(
            'host'                => 'eho.name1 COLLATE latin1_general_ci',
            'service'             => 'eho.name2 COLLATE latin1_general_ci',
            'host_name'           => 'eho.name1 COLLATE latin1_general_ci',
            'service_description' => 'eho.name2 COLLATE latin1_general_ci',
            'object_type'         => "CASE WHEN eho.objecttype_id = 1 THEN 'host' ELSE 'service' END",
            'timestamp'       => 'UNIX_TIMESTAMP(eh.state_time)',
            'raw_timestamp'   => 'eh.state_time',
            'state'           => 'eh.state',
//            'last_state'      => 'eh.last_state',
//            'last_hard_state' => 'eh.last_hard_state',
            'attempt'         => 'eh.attempt',
            'max_attempts'    => 'eh.max_attempts',
            'output'          => 'eh.output', // we do not want long_output
            'problems'        => 'CASE WHEN eh.state = 0 OR eh.state IS NULL THEN 0 ELSE 1 END',
            'type'  => 'eh.type',
        )
    );

    protected function getDefaultColumns()
    {
        return $this->columnMap['eventhistory'];
    }

    protected function joinBaseTables()
    {
        $start = date('Y-m-d H:i:s', time() - 3600 * 24 * 30);
        $end   = date('Y-m-d H:i:s');
        $start = null;
        $end  = null;

        $history = $this->db->select()->from(
            $this->prefix . 'statehistory',
            array(
                'state_time' => 'state_time',
                'object_id'  => 'object_id',
                'type'       => "(CASE WHEN state_type = 1 THEN 'hard_state' ELSE 'soft_state' END)",
                'state'      => 'state',
                'state_type' => 'state_type',
                'output'     => 'output',
                'attempt'      => 'current_check_attempt',
                'max_attempts' => 'max_check_attempts',
            )
        );
        if ($start !== null) {
            $history->where('state_time >= ?', $start);
        }
        // ->where('state_type = 1') ??
        if ($end !== null) {
            $history->where('state_time <= ?', $end);
        }

        $dt_start = $this->db->select()->from(
            $this->prefix . 'downtimehistory',
            array(
                'state_time' => 'actual_start_time',
                'object_id'  => 'object_id',
                'type'       => "('dt_start')",
                'state'      => '(NULL)',
                'state_type' => '(NULL)',
//                'output'     => "CONCAT('[', author_name, '] ', comment_data)",
                'output'     => "('[' || author_name || '] ' || comment_data)",
                'attempt'      => '(NULL)',
                'max_attempts' => '(NULL)',
            )
        );
        if ($start !== null) {
            $dt_start->where('actual_start_time >= ?', $start);
        }
        if ($end !== null) {
            $dt_start->where('actual_start_time <= ?', $end);
        }

        // TODO: check was_cancelled
        $dt_end = $this->db->select()->from(
            $this->prefix . 'downtimehistory',
            array(
                'state_time' => 'actual_end_time',
                'object_id'  => 'object_id',
                'type'       => "('dt_end')",
                'state'      => '(NULL)',
                'state_type' => '(NULL)',
//                 'output'     => "CONCAT('[', author_name, '] ', comment_data)",
                'output'     => "('[' || author_name || '] ' || comment_data)",
                'attempt'      => '(NULL)',
                'max_attempts' => '(NULL)',
            )
        );
        if ($start !== null) {
            $dt_end->where('actual_end_time >= ?', $start);
        }
        if ($end !== null) {
            $dt_end->where('actual_end_time <= ?', $end);
        }

        $comments = $this->db->select()->from(
            $this->prefix . 'commenthistory',
            array(
                'state_time' => 'comment_time',
                'object_id'  => 'object_id',
                'type'       => "(CASE entry_type WHEN 1 THEN 'comment' WHEN 2 THEN 'dt_comment' WHEN 3 THEN 'flapping' WHEN 4 THEN 'ack' END)",
                'state'      => '(NULL)',
                'state_type' => '(NULL)',
//                 'output'     => "CONCAT('[', author_name, '] ', comment_data)",
                'output'     => "('[' || author_name || '] ' || comment_data)",
                'attempt'      => '(NULL)',
                'max_attempts' => '(NULL)',
            )
        );

        if ($start !== null) {
            $comments->where('comment_time >= ?', $start);
        }
        if ($end !== null) {
            $comments->where('comment_time <= ?', $end);
        }

        // This is one of the db-specific workarounds that could be abstracted
        // in a better way:
        switch ($this->ds->getConnection()->getDbType()) {
            case 'mysql':
                $concat_contacts = "GROUP_CONCAT(c.alias ORDER BY c.alias SEPARATOR ', ')";
                break;
            case 'pgsql':
                // TODO: Find a way to "ORDER" these:
                $concat_contacts = "ARRAY_TO_STRING(ARRAY_AGG(c.alias), ', ')";
                break;
            case 'oracle':
                // TODO: This is only valid for Oracle >= 11g Release 2.
                $concat_contacts = "LISTAGG(c.alias, ', ') WITHIN GROUP (ORDER BY c.alias)";
                // Alternatives:
                //
                //   RTRIM(XMLAGG(XMLELEMENT(e, column_name, ',').EXTRACT('//text()')),
                //
                //   not supported and not documented but works since 10.1,
                //   however it is NOT always present;
                //   WM_CONCAT(c.alias)
                break;
            default:
                die('Not yet'); // TODO: Proper Exception
        }

        $cndetails = $this->db->select()->from(
            array('cn' => $this->prefix . 'contactnotifications'),
            array(
                'notification_id' => 'notification_id',
                'cnt'      => 'COUNT(*)',
                'contacts' => $concat_contacts
            )
        )->join(
            array('c' => $this->prefix . 'contacts'),
            'cn.contact_object_id = c.contact_object_id',
            array()
        )->group('notification_id');

        $notifications = $this->db->select()->from(
            array('n' => $this->prefix . 'notifications'),
            array(
                'state_time' => 'start_time',
                'object_id'  => 'object_id',
                'type'       => "('notify')",
                'state'      => 'state',
                'state_type' => '(NULL)',
               //  'output'     => "CONCAT('[', cndetails.contacts, '] ', n.output)",
                'output'     => "('[' || cndetails.contacts || '] ' || n.output)",
                'attempt'      => '(NULL)',
                'max_attempts' => '(NULL)',
            )
        )->join(
            array('cndetails' => $cndetails),
            'cndetails.notification_id = n.notification_id',
            array()
        );
        
        if ($start !== null) {
            $notifications->where('start_time >= ?', $start);
        }
        if ($end !== null) {
            $notifications->where('start_time <= ?', $end);
        }             

        $this->subQueries = array(
            $history,
            $dt_start,
            $dt_end,
            $comments,
            $notifications
        );
        $sub = $this->db->select()->union($this->subQueries, \Zend_Db_Select::SQL_UNION_ALL);

        $this->baseQuery = $this->db->select()->from(
            array('eho' => $this->prefix . 'objects'),
            array()
        )->join(
            array('eh' => $sub),
            'eho.' . $this->object_id
            . ' = eh.' . $this->object_id
            . ' AND eho.is_active = 1',
            array()
        );

        $this->joinedVirtualTables = array('eventhistory' => true);
    }

    // TODO: This duplicates code from AbstractQuery
    protected function applyAllFilters()
    {
        $filters = array();

        $host = null;
        $service = null;

        foreach ($this->filters as $f) {
            $alias = $f[0];
            $value = $f[1];
            $this->requireColumn($alias);

            if ($this->hasAliasName($alias)) {
                $col = $this->aliasToColumnName($alias);
            } else {
                throw new ProgrammingError(
                    'If you finished here, code has been messed up'
                );
            }

            if (in_array($alias, array('host', 'host_name'))) {
                $host = $value;
                continue;
            }
            if (in_array($alias, array('service', 'service_description'))) {
                $service = $value;
                continue;
            }

            $this->baseQuery->where($this->prepareFilterStringForColumn($col, $value));
        }

        $objectQuery = $this->db->select()->from(
            $this->prefix . 'objects',
            $this->object_id
        )->where('is_active = 1');

        if ($service === '*') {
            $objectQuery->where('name1 = ?', $host)
                ->where('objecttype_id IN (1, 2)');
        } elseif ($service) {
            $objectQuery->where('name1 = ?', $host)
                ->where('name2 = ?', $service)
                ->where('objecttype_id = 2');
        } else {
            $objectQuery->where('name1 = ?', $host)
                        ->where('objecttype_id = 1');
        }
        $objectId = $this->db->fetchCol($objectQuery);
        foreach ($this->subQueries as $query) {
            $query->where('object_id IN (?)', $objectId);
        }
    }
}
