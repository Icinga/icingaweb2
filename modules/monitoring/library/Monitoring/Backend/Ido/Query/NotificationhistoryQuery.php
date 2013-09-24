<?php

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class NotificationhistoryQuery extends AbstractQuery
{
    protected $columnMap = array(
        'history' => array(
            'timestamp'     => 'UNIX_TIMESTAMP(n.start_time)',
            'raw_timestamp' => 'n.start_time',
            'state_time'    => 'n.start_time',
            'object_id'     => 'object_id',
            'type'          => "('notify')",
            'state'         => 'state',
            'state_type'    => '(NULL)',
            'output'        => null,
            'attempt'       => '(NULL)',
            'max_attempts'  => '(NULL)',
        )
    );

    protected function joinBaseTables()
    {
//"('[' || cndetails.contacts || '] ' || n.output)"
        // This is one of the db-specific workarounds that could be abstracted
        // in a better way:
        switch ($this->ds->getDbType()) {
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
$this->columnMap['history']['output'] = "('[' || $concat_contacts || '] ' || n.output)";
/*
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
*/
        $this->baseQuery = $this->db->select()->from(
            array('n' => $this->prefix . 'notifications'),
            array()
        )->join(
            array('cn' => $this->prefix . 'contactnotifications'),
            'cn.notification_id = n.notification_id',
            array()
        )->joinLeft(
            array('c' => $this->prefix . 'contacts'),
            'cn.contact_object_id = c.contact_object_id',
            array()
        )->group('cn.notification_id')

        /*->join(
            array('cndetails' => $cndetails),
            'cndetails.notification_id = n.notification_id',
            array()
        )*/
        ;

        $this->joinedVirtualTables = array('history' => true);
    }

}
