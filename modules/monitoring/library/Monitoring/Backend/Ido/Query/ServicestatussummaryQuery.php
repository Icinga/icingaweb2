<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

/**
 * Query for service status summary
 *
 * TODO(el): Allow to switch between hard and soft states
 */
class ServicestatussummaryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $allowCustomVars = true;

    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'hostgroups' => array(
            'hostgroup'         => 'hgo.name1 COLLATE latin1_general_ci',
            'hostgroup_alias'   => 'hg.alias COLLATE latin1_general_ci',
            'hostgroup_name'    => 'hgo.name1'
        ),
        'servicegroups' => array(
            'servicegroup'          => 'sgo.name1 COLLATE latin1_general_ci',
            'servicegroup_name'     => 'sgo.name1',
            'servicegroup_alias'    => 'sg.alias COLLATE latin1_general_ci'
        ),
        'servicestatussummary' => array(
            'host'                                          => 'so.name1 COLLATE latin1_general_ci',
            'host_alias'                                    => 'h.alias COLLATE latin1_general_ci',
            'host_display_name'                             => 'h.display_name COLLATE latin1_general_ci',
            'host_name'                                     => 'so.name1',
            'object_type'                                   => '(\'service\')',
            'service'                                       => 'so.name2 COLLATE latin1_general_ci',
            'service_description'                           => 'so.name2',
            'service_display_name'                          => 's.display_name COLLATE latin1_general_ci',
            'service_host'                                  => 'so.name1 COLLATE latin1_general_ci',
            'service_host_name'                             => 'so.name1',
            'services_critical'                             => 'SUM(CASE WHEN ss.has_been_checked != 1 THEN 0 ELSE CASE WHEN ss.current_state = 2 THEN 1 ELSE 0 END END)',
            'services_critical_handled'                     => 'SUM(CASE WHEN ss.has_been_checked != 1 THEN 0 ELSE CASE WHEN ss.current_state = 2 AND (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth) > 0 THEN 1 ELSE 0 END END)',
            'services_critical_handled_last_state_change'   => 'MAX(CASE WHEN ss.has_been_checked != 1 THEN NULL ELSE CASE WHEN ss.current_state = 2 AND (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth) > 0 THEN UNIX_TIMESTAMP(ss.last_state_change) ELSE NULL END END)',
            'services_critical_unhandled'                   => 'SUM(CASE WHEN ss.has_been_checked != 1 THEN 0 ELSE CASE WHEN ss.current_state = 2 AND (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth) = 0 THEN 1 ELSE 0 END END)',
            'services_critical_unhandled_last_state_change' => 'MAX(CASE WHEN ss.has_been_checked != 1 THEN NULL ELSE CASE WHEN ss.current_state = 2 AND (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth) = 0 THEN UNIX_TIMESTAMP(ss.last_state_change) ELSE NULL END END)',
            'services_ok'                                   => 'SUM(CASE WHEN ss.has_been_checked != 1 THEN 0 ELSE CASE WHEN ss.current_state = 0 THEN 1 ELSE 0 END END)',
            'services_ok_last_state_change'                 => 'MAX(CASE WHEN ss.has_been_checked != 1 THEN NULL ELSE CASE WHEN ss.current_state = 0 THEN UNIX_TIMESTAMP(ss.last_state_change) ELSE NULL END END)',
            'services_pending'                              => 'SUM(CASE WHEN ss.has_been_checked != 1 THEN 1 ELSE 0 END)',
            'services_pending_last_state_change'            => 'MAX(CASE WHEN ss.has_been_checked != 1 THEN UNIX_TIMESTAMP(ss.last_state_change) ELSE NULL END)',
            'services_total'                                => 'SUM(1)',
            'services_unknown'                              => 'SUM(CASE WHEN ss.has_been_checked != 1 THEN 0 ELSE CASE WHEN ss.current_state = 3 THEN 1 ELSE 0 END END)',
            'services_unknown_handled'                      => 'SUM(CASE WHEN ss.has_been_checked != 1 THEN 0 ELSE CASE WHEN ss.current_state = 3 AND (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth) > 0 THEN 1 ELSE 0 END END)',
            'services_unknown_handled_last_state_change'    => 'MAX(CASE WHEN ss.has_been_checked != 1 THEN NULL ELSE CASE WHEN ss.current_state = 3 AND (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth) > 0 THEN UNIX_TIMESTAMP(ss.last_state_change) ELSE NULL END END)',
            'services_unknown_unhandled'                    => 'SUM(CASE WHEN ss.has_been_checked != 1 THEN 0 ELSE CASE WHEN ss.current_state = 3 AND (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth) = 0 THEN 1 ELSE 0 END END)',
            'services_unknown_unhandled_last_state_change'  => 'MAX(CASE WHEN ss.has_been_checked != 1 THEN NULL ELSE CASE WHEN ss.current_state = 3 AND (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth) = 0 THEN UNIX_TIMESTAMP(ss.last_state_change) ELSE NULL END END)',
            'services_warning'                              => 'SUM(CASE WHEN ss.has_been_checked != 1 THEN 0 ELSE CASE WHEN ss.current_state = 1 THEN 1 ELSE 0 END END)',
            'services_warning_handled'                      => 'SUM(CASE WHEN ss.has_been_checked != 1 THEN 0 ELSE CASE WHEN ss.current_state = 1 AND (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth) > 0 THEN 1 ELSE 0 END END)',
            'services_warning_handled_last_state_change'    => 'MAX(CASE WHEN ss.has_been_checked != 1 THEN NULL ELSE CASE WHEN ss.current_state = 1 AND (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth) > 0 THEN UNIX_TIMESTAMP(ss.last_state_change) ELSE NULL END END)',
            'services_warning_unhandled'                    => 'SUM(CASE WHEN ss.has_been_checked != 1 THEN 0 ELSE CASE WHEN ss.current_state = 1 AND (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth) = 0 THEN 1 ELSE 0 END END)',
            'services_warning_unhandled_last_state_change'  => 'MAX(CASE WHEN ss.has_been_checked != 1 THEN NULL ELSE CASE WHEN ss.current_state = 1 AND (ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth) = 0 THEN UNIX_TIMESTAMP(ss.last_state_change) ELSE NULL END END)'
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        $this->select->from(
            array('so' => $this->prefix . 'objects'),
            array()
        )->join(
            array('s' => $this->prefix . 'services'),
            's.service_object_id = so.object_id',
            array()
        )->join(
            array('ss' => $this->prefix . 'servicestatus'),
            'ss.service_object_id = so.object_id',
            array()
        )->join(
            array('h' => $this->prefix . 'hosts'),
            'h.host_object_id = s.host_object_id',
            array()
        )->where(
            'so.is_active = ?',
            1
        )->where(
            'so.objecttype_id = ?',
            2
        );
        $this->joinedVirtualTables['servicestatussummary'] = true;
        // Provide table services for custom var joins
        $this->joinedVirtualTables['services'] = true;
    }

    /**
     * Join host groups
     */
    protected function joinHostgroups()
    {
        $this->select->join(
            array('hgm' => $this->prefix . 'hostgroup_members'),
            'hgm.host_object_id = s.host_object_id',
            array()
        )->join(
            array('hg' => $this->prefix . 'hostgroups'),
            'hg.hostgroup_id = hgm.hostgroup_id',
            array()
        )->join(
            array('hgo' => $this->prefix . 'objects'),
            'hgo.object_id = hg.hostgroup_object_id',
            array()
        )->where(
            'hgo.is_active = ?',
            1
        )->where(
            'hgo.objecttype_id = ?',
            3
        );
    }

    /**
     * Join host status
     */
    protected function joinHoststatus()
    {
        $this->requireVirtualTable('services');
        $this->select->join(
            array('hs' => $this->prefix . 'hoststatus'),
            'hs.host_object_id = so.object_id',
            array()
        );
    }

    /**
     * Join service groups
     */
    protected function joinServicegroups()
    {
        $this->select->join(
            array('sgm' => $this->prefix . 'servicegroup_members'),
            'sgm.service_object_id = so.object_id',
            array()
        )->join(
            array('sg' => $this->prefix . 'servicegroups'),
            'sgm.servicegroup_id = sg.' . $this->servicegroup_id,
            array()
        )->join(
            array('sgo' => $this->prefix . 'objects'),
            'sgo.object_id = sg.servicegroup_object_id',
            array()
        )->where(
            'sgo.is_active = ?',
            1
        )
        ->where(
            'sgo.objecttype_id = ?',
            4
        )
        ->group('sgo.name1');
    }
}
