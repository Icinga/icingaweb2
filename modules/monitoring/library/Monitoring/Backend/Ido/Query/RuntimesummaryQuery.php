<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Select;

/**
 * Query check summaries out of database
 */
class RuntimesummaryQuery extends IdoQuery
{
    protected $columnMap = array(
        'runtimesummary' => array(
            'check_type'                => 'check_type',
            'active_checks_enabled'     => 'active_checks_enabled',
            'passive_checks_enabled'    => 'passive_checks_enabled',
            'execution_time'            => 'execution_time',
            'latency'                   => 'latency',
            'object_count'              => 'object_count',
            'object_type'               => 'object_type'
        )
    );

    protected function joinBaseTables()
    {
        $hosts = $this->db->select()->from(
            array('ho' => $this->prefix . 'objects'),
            array()
        )->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'ho.object_id = hs.host_object_id AND ho.is_active = 1 AND ho.objecttype_id = 1',
            array()
        )->columns(
            array(
                'check_type'                => 'CASE '
                    . 'WHEN hs.active_checks_enabled = 0 AND hs.passive_checks_enabled = 1 THEN \'passive\' '
                    . 'WHEN hs.active_checks_enabled = 1 THEN \'active\' '
                    . 'END',
                'active_checks_enabled'     => 'hs.active_checks_enabled',
                'passive_checks_enabled'    => 'hs.passive_checks_enabled',
                'execution_time'            => 'SUM(hs.execution_time)',
                'latency'                   => 'SUM(hs.latency)',
                'object_count'              => 'COUNT(*)',
                'object_type'               => "('host')"
            )
        )->group('check_type')->group('active_checks_enabled')->group('passive_checks_enabled');

        $services = $this->db->select()->from(
            array('so' => $this->prefix . 'objects'),
            array()
        )->join(
            array('ss' => $this->prefix . 'servicestatus'),
            'so.object_id = ss.service_object_id AND so.is_active = 1 AND so.objecttype_id = 2',
            array()
        )->columns(
            array(
                'check_type'                => 'CASE '
                    . 'WHEN ss.active_checks_enabled = 0 AND ss.passive_checks_enabled = 1 THEN \'passive\' '
                    . 'WHEN ss.active_checks_enabled = 1 THEN \'active\' '
                    . 'END',
                'active_checks_enabled'     => 'ss.active_checks_enabled',
                'passive_checks_enabled'    => 'ss.passive_checks_enabled',
                'execution_time'            => 'SUM(ss.execution_time)',
                'latency'                   => 'SUM(ss.latency)',
                'object_count'              => 'COUNT(*)',
                'object_type'               => "('service')"
            )
        )->group('check_type')->group('active_checks_enabled')->group('passive_checks_enabled');

        $union = $this->db->select()->union(
            array('s' => $services, 'h' => $hosts),
            Zend_Db_Select::SQL_UNION_ALL
        );

        $this->select->from(array('hs' => $union));

        $this->joinedVirtualTables = array('runtimesummary' => true);
    }
}
