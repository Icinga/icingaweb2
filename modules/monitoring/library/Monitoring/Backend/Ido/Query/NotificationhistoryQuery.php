<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class NotificationhistoryQuery extends IdoQuery
{
    protected $columnMap = array(
        'history' => array(
            'timestamp'     => 'UNIX_TIMESTAMP(n.start_time)',
            'raw_timestamp' => 'n.start_time',
            'state_time'    => 'n.start_time',
            'object_id'     => 'n.object_id',
            'type'          => "('notify')",
            'state'         => 'n.state',
            'state_type'    => '(NULL)',
            'output'        => null,
            'attempt'       => '(NULL)',
            'max_attempts'  => '(NULL)',

            'host'                => 'o.name1 COLLATE latin1_general_ci',
            'service'             => 'o.name2 COLLATE latin1_general_ci',
            'host_name'           => 'o.name1 COLLATE latin1_general_ci',
            'service_description' => 'o.name2 COLLATE latin1_general_ci',
            'service_host_name'   => 'o.name1 COLLATE latin1_general_ci',
            'service_description' => 'o.name2 COLLATE latin1_general_ci',
            'object_type'         => "CASE WHEN o.objecttype_id = 1 THEN 'host' ELSE 'service' END"
        )
    );

    public function whereToSql($col, $sign, $expression)
    {
        if ($col === 'UNIX_TIMESTAMP(n.start_time)') {
            return 'n.start_time ' . $sign . ' ' . $this->timestampForSql($this->valueToTimestamp($expression));
        } else {
            return parent::whereToSql($col, $sign, $expression);
        }
    }

    protected function joinBaseTables()
    {
        switch ($this->ds->getDbType()) {
            case 'mysql':
                $concattedContacts = "GROUP_CONCAT(c.alias ORDER BY c.alias SEPARATOR ', ')";
                break;
            case 'pgsql':
                // TODO: Find a way to order the contact alias list:
                $concattedContacts = "ARRAY_TO_STRING(ARRAY_AGG(c.alias), ', ')";
                break;
            case 'oracle':
                // TODO: This is only valid for Oracle >= 11g Release 2
                $concattedContacts = "LISTAGG(c.alias, ', ') WITHIN GROUP (ORDER BY c.alias)";
                // Alternatives:
                //
                //   RTRIM(XMLAGG(XMLELEMENT(e, column_name, ',').EXTRACT('//text()')),
                //
                //   not supported and not documented but works since 10.1,
                //   however it is NOT always present:
                //   WM_CONCAT(c.alias)
                break;
        }

        $this->columnMap['history']['output'] = "('[' || $concattedContacts || '] ' || n.output)";

        $this->select->from(
            array('o' => $this->prefix . 'objects'),
            array()
        )->join(
            array('n' => $this->prefix . 'notifications'),
            'o.' . $this->object_id . ' = n.' . $this->object_id . ' AND o.is_active = 1',
            array()
        )->join(
            array('cn' => $this->prefix . 'contactnotifications'),
            'cn.notification_id = n.notification_id',
            array()
        )->joinLeft(
            array('c' => $this->prefix . 'contacts'),
            'cn.contact_object_id = c.contact_object_id',
            array()
        )->group('cn.notification_id');

        // TODO: hmmmm...
        if ($this->ds->getDbType() === 'pgsql') {
            $this->select->group('n.object_id')
                ->group('n.start_time')
                ->group('n.output')
                ->group('n.state')
                ->group('o.objecttype_id');
        }

        $this->joinedVirtualTables = array('history' => true);
    }

}
