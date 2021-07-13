<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Select;

/**
 * Query for host and service group summaries
 */
class GroupsummaryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'hoststatussummary' => array(
            'hostgroup'                                     => 'hostgroup COLLATE latin1_general_ci',
            'hostgroup_alias'                               => 'hostgroup_alias COLLATE latin1_general_ci',
            'hostgroup_name'                                => 'hostgroup_name',
            'hosts_up'                                      => 'SUM(object_type = \'host\' AND state = 0)',
            'hosts_unreachable'                             => 'SUM(object_type = \'host\' AND state = 2)',
            'hosts_unreachable_handled'                     => 'SUM(object_type = \'host\' AND state = 2 AND acknowledged + in_downtime != 0)',
            'hosts_unreachable_unhandled'                   => 'SUM(object_type = \'host\' AND state = 2 AND acknowledged + in_downtime = 0)',
            'hosts_down'                                    => 'SUM(object_type = \'host\' AND state = 1)',
            'hosts_down_handled'                            => 'SUM(object_type = \'host\' AND state = 1 AND acknowledged + in_downtime != 0)',
            'hosts_down_last_state_change_handled'          => 'MAX(CASE WHEN object_type = \'host\' AND state = 1 AND acknowledged + in_downtime != 0 THEN state_change ELSE 0 END)',
            'hosts_down_last_state_change_unhandled'        => 'MAX(CASE WHEN object_type = \'host\' AND state = 1 AND acknowledged + in_downtime = 0 THEN state_change ELSE 0 END)',
            'hosts_down_unhandled'                          => 'SUM(object_type = \'host\' AND state = 1 AND acknowledged + in_downtime = 0)',
            'hosts_pending'                                 => 'SUM(object_type = \'host\' AND state = 99)',
            'hosts_pending_last_state_change'               => 'MAX(CASE WHEN object_type = \'host\' AND state = 99 THEN state_change ELSE 0 END)',
            'hosts_severity'                                => 'MAX(CASE WHEN object_type = \'host\' THEN severity ELSE 0 END)',
            'hosts_total'                                   => 'SUM(object_type = \'host\')',
            'hosts_unreachable_last_state_change_handled'   => 'MAX(CASE WHEN object_type = \'host\' AND state = 2 AND acknowledged + in_downtime != 0 THEN state_change ELSE 0 END)',
            'hosts_unreachable_last_state_change_unhandled' => 'MAX(CASE WHEN object_type = \'host\' AND state = 2 AND acknowledged + in_downtime = 0 THEN state_change ELSE 0 END)',
            'hosts_up_last_state_change'                    => 'MAX(CASE WHEN object_type = \'host\' AND state = 0 THEN state_change ELSE 0 END)'
        ),
        'servicestatussummary' => array(
            'servicegroup'                                  => 'servicegroup COLLATE latin1_general_ci',
            'servicegroup_alias'                            => 'servicegroup_alias COLLATE latin1_general_ci',
            'servicegroup_name'                             => 'servicegroup_name',
            'services_critical'                             => 'SUM(object_type = \'service\' AND state = 2)',
            'services_critical_handled'                     => 'SUM(object_type = \'service\' AND state = 2 AND acknowledged + in_downtime + host_state > 0)',
            'services_critical_last_state_change_handled'   => 'MAX(CASE WHEN object_type = \'service\' AND state = 2 AND acknowledged + in_downtime + host_state > 0 THEN state_change ELSE 0 END)',
            'services_critical_last_state_change_unhandled' => 'MAX(CASE WHEN object_type = \'service\' AND state = 2 AND acknowledged + in_downtime + host_state = 0 THEN state_change ELSE 0 END)',
            'services_critical_unhandled'                   => 'SUM(object_type = \'service\' AND state = 2 AND acknowledged + in_downtime + host_state = 0)',
            'services_ok'                                   => 'SUM(object_type = \'service\' AND state = 0)',
            'services_ok_last_state_change'                 => 'MAX(CASE WHEN object_type = \'service\' AND state = 0 THEN state_change ELSE 0 END)',
            'services_pending'                              => 'SUM(object_type = \'service\' AND state = 99)',
            'services_pending_last_state_change'            => 'MAX(CASE WHEN object_type = \'service\' AND state = 99 THEN state_change ELSE 0 END)',
            'services_severity'                             => 'MAX(CASE WHEN object_type = \'service\' THEN severity ELSE 0 END)',
            'services_total'                                => 'SUM(object_type = \'service\')',
            'services_unknown'                              => 'SUM(object_type = \'service\' AND state = 3)',
            'services_unknown_handled'                      => 'SUM(object_type = \'service\' AND state = 3 AND acknowledged + in_downtime + host_state > 0)',
            'services_unknown_last_state_change_handled'    => 'MAX(CASE WHEN object_type = \'service\' AND state = 3 AND acknowledged + in_downtime + host_state > 0 THEN state_change ELSE 0 END)',
            'services_unknown_last_state_change_unhandled'  => 'MAX(CASE WHEN object_type = \'service\' AND state = 3 AND acknowledged + in_downtime + host_state = 0 THEN state_change ELSE 0 END)',
            'services_unknown_unhandled'                    => 'SUM(object_type = \'service\' AND state = 3 AND acknowledged + in_downtime + host_state = 0)',
            'services_warning'                              => 'SUM(object_type = \'service\' AND state = 1)',
            'services_warning_handled'                      => 'SUM(object_type = \'service\' AND state = 1 AND acknowledged + in_downtime + host_state > 0)',
            'services_warning_last_state_change_handled'    => 'MAX(CASE WHEN object_type = \'service\' AND state = 1 AND acknowledged + in_downtime + host_state > 0 THEN state_change ELSE 0 END)',
            'services_warning_last_state_change_unhandled'  => 'MAX(CASE WHEN object_type = \'service\' AND state = 1 AND acknowledged + in_downtime + host_state = 0 THEN state_change ELSE 0 END)',
            'services_warning_unhandled'                    => 'SUM(object_type = \'service\' AND state = 1 AND acknowledged + in_downtime + host_state = 0)'
        )
    );

    /**
     * {@inheritdoc}
     */
    protected $useSubqueryCount = true;

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $columns = array(
            'object_type',
            'host_state'
        );

        if (in_array('servicegroup', $this->desiredColumns) || in_array('servicegroup_name', $this->desiredColumns)) {
            $columns[] = 'servicegroup';
            $columns[] = 'servicegroup_name';
            $columns[] = 'servicegroup_alias';
            $groupColumns = array('servicegroup_name', 'servicegroup_alias');
        } else {
            $columns[] = 'hostgroup';
            $columns[] = 'hostgroup_name';
            $columns[] = 'hostgroup_alias';
            $groupColumns = array('hostgroup_name', 'hostgroup_alias');
        }
        $hosts = $this->createSubQuery(
            'Hoststatus',
            $columns + array(
                'state'         => 'host_state',
                'acknowledged'  => 'host_acknowledged',
                'in_downtime'   => 'host_in_downtime',
                'state_change'  => 'host_last_state_change',
                'severity'      => 'host_severity'
            )
        );
        if (in_array('servicegroup_name', $this->desiredColumns)) {
            $hosts->group(array(
                'sgo.name1',
                'ho.object_id',
                'sg.alias',
                'state',
                'acknowledged',
                'in_downtime',
                'state_change',
                'severity'
            ));
        }
        $services = $this->createSubQuery(
            'Status',
            $columns + array(
                'state'         => 'service_state',
                'acknowledged'  => 'service_acknowledged',
                'in_downtime'   => 'service_in_downtime',
                'state_change'  => 'service_last_state_change',
                'severity'      => 'service_severity'
            )
        );
        $union = $this->db->select()->union(array($hosts, $services), Zend_Db_Select::SQL_UNION_ALL);
        $this->select->from(array('statussummary' => $union), array())->group($groupColumns);
        $this->joinedVirtualTables = array(
            'servicestatussummary'  => true,
            'hoststatussummary'     => true
        );
    }
}
