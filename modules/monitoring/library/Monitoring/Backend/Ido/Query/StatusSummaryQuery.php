<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Select;

class StatusSummaryQuery extends IdoQuery
{
    protected $subHosts;

    protected $subServices;

    protected $columnMap = array(
        'services' => array(
            'service_host_name' => 'so.name1',
            'service_description' => 'so.name2',
        ),
        'hoststatussummary' => array(
            'hosts_up'                              => 'SUM(CASE WHEN object_type = \'host\' AND state = 0 THEN 1 ELSE 0 END)',
            'hosts_up_not_checked'                  => 'SUM(CASE WHEN object_type = \'host\' AND state = 0 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'hosts_pending'                         => 'SUM(CASE WHEN object_type = \'host\' AND state = 99 THEN 1 ELSE 0 END)',
            'hosts_pending_not_checked'             => 'SUM(CASE WHEN object_type = \'host\' AND state = 99 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'hosts_down'                            => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 THEN 1 ELSE 0 END)',
            'hosts_down_handled'                    => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND acknowledged + in_downtime > 0 THEN 1 ELSE 0 END)',
            'hosts_down_unhandled'                  => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND acknowledged + in_downtime = 0 THEN 1 ELSE 0 END)',
            'hosts_down_passive'                    => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'hosts_down_not_checked'                => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'hosts_unreachable'                     => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 THEN 1 ELSE 0 END)',
            'hosts_unreachable_handled'             => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 AND acknowledged + in_downtime > 0 THEN 1 ELSE 0 END)',
            'hosts_unreachable_unhandled'           => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 AND acknowledged + in_downtime = 0 THEN 1 ELSE 0 END)',
            'hosts_unreachable_passive'             => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'hosts_unreachable_not_checked'         => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'hosts_active'                          => 'SUM(CASE WHEN object_type = \'host\' AND is_active_checked = 1 THEN 1 ELSE 0 END)',
            'hosts_passive'                         => 'SUM(CASE WHEN object_type = \'host\' AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'hosts_not_checked'                     => 'SUM(CASE WHEN object_type = \'host\' AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'hosts_not_processing_event_handlers'   => 'SUM(CASE WHEN object_type = \'host\' AND is_processing_events = 0 THEN 1 ELSE 0 END)',
            'hosts_not_triggering_notifications'    => 'SUM(CASE WHEN object_type = \'host\' AND is_triggering_notifications = 0 THEN 1 ELSE 0 END)',
            'hosts_without_flap_detection'          => 'SUM(CASE WHEN object_type = \'host\' AND is_allowed_to_flap = 0 THEN 1 ELSE 0 END)',
            'hosts_flapping'                        => 'SUM(CASE WHEN object_type = \'host\' AND is_flapping = 1 THEN 1 ELSE 0 END)'
        ),
        'servicestatussummary' => array(
            'services_total'                            => 'SUM(CASE WHEN object_type = \'service\' THEN 1 ELSE 0 END)',
            'services_problem'                          => 'SUM(CASE WHEN object_type = \'service\' AND state > 0 THEN 1 ELSE 0 END)',
            'services_problem_handled'                  => 'SUM(CASE WHEN object_type = \'service\' AND state > 0 AND acknowledged + in_downtime + host_problem > 0 THEN 1 ELSE 0 END)',
            'services_problem_unhandled'                => 'SUM(CASE WHEN object_type = \'service\' AND state > 0 AND acknowledged + in_downtime + host_problem = 0 THEN 1 ELSE 0 END)',
            'services_ok'                               => 'SUM(CASE WHEN object_type = \'service\' AND state = 0 THEN 1 ELSE 0 END)',
            'services_ok_not_checked'                   => 'SUM(CASE WHEN object_type = \'service\' AND state = 0 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_pending'                          => 'SUM(CASE WHEN object_type = \'service\' AND state = 99 THEN 1 ELSE 0 END)',
            'services_pending_not_checked'              => 'SUM(CASE WHEN object_type = \'service\' AND state = 99 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_warning'                          => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 THEN 1 ELSE 0 END)',
            'services_warning_handled'                  => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND acknowledged + in_downtime + host_problem > 0 THEN 1 ELSE 0 END)',
            'services_warning_unhandled'                => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND acknowledged + in_downtime + host_problem = 0 THEN 1 ELSE 0 END)',
            'services_warning_passive'                  => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_warning_not_checked'              => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_critical'                         => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 THEN 1 ELSE 0 END)',
            'services_critical_handled'                 => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND acknowledged + in_downtime + host_problem > 0 THEN 1 ELSE 0 END)',
            'services_critical_unhandled'               => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND acknowledged + in_downtime + host_problem = 0 THEN 1 ELSE 0 END)',
            'services_critical_passive'                 => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_critical_not_checked'             => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_unknown'                          => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 THEN 1 ELSE 0 END)',
            'services_unknown_handled'                  => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND acknowledged + in_downtime + host_problem > 0 THEN 1 ELSE 0 END)',
            'services_unknown_unhandled'                => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND acknowledged + in_downtime + host_problem = 0 THEN 1 ELSE 0 END)',
            'services_unknown_passive'                  => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_unknown_not_checked'              => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_active'                           => 'SUM(CASE WHEN object_type = \'service\' AND is_active_checked = 1 THEN 1 ELSE 0 END)',
            'services_passive'                          => 'SUM(CASE WHEN object_type = \'service\' AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_not_checked'                      => 'SUM(CASE WHEN object_type = \'service\' AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_not_processing_event_handlers'    => 'SUM(CASE WHEN object_type = \'service\' AND is_processing_events = 0 THEN 1 ELSE 0 END)',
            'services_not_triggering_notifications'     => 'SUM(CASE WHEN object_type = \'service\' AND is_triggering_notifications = 0 THEN 1 ELSE 0 END)',
            'services_without_flap_detection'           => 'SUM(CASE WHEN object_type = \'service\' AND is_allowed_to_flap = 0 THEN 1 ELSE 0 END)',
            'services_flapping'                         => 'SUM(CASE WHEN object_type = \'service\' AND is_flapping = 1 THEN 1 ELSE 0 END)',

/*
NOTE: in case you might wonder, please see #7303. As a quickfix I did:

:%s/(host_state = 0 OR host_state = 99)/host_state != 1 AND host_state != 2/g
:%s/(host_state = 1 OR host_state = 2)/host_state != 0 AND host_state != 99/g

We have to find a better solution here.

*/
            'services_ok_on_ok_hosts'                           => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 0 THEN 1 ELSE 0 END)',
            'services_ok_not_checked_on_ok_hosts'               => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 0 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_pending_on_ok_hosts'                      => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 99 THEN 1 ELSE 0 END)',
            'services_pending_not_checked_on_ok_hosts'          => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 99 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_warning_handled_on_ok_hosts'              => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 1 AND acknowledged + in_downtime > 0 THEN 1 ELSE 0 END)',
            'services_warning_unhandled_on_ok_hosts'            => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 1 AND acknowledged + in_downtime = 0 THEN 1 ELSE 0 END)',
            'services_warning_passive_on_ok_hosts'              => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 1 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_warning_not_checked_on_ok_hosts'          => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 1 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_critical_handled_on_ok_hosts'             => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 2 AND acknowledged + in_downtime > 0 THEN 1 ELSE 0 END)',
            'services_critical_unhandled_on_ok_hosts'           => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 2 AND acknowledged + in_downtime = 0 THEN 1 ELSE 0 END)',
            'services_critical_passive_on_ok_hosts'             => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 2 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_critical_not_checked_on_ok_hosts'         => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 2 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_unknown_handled_on_ok_hosts'              => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 3 AND acknowledged + in_downtime > 0 THEN 1 ELSE 0 END)',
            'services_unknown_unhandled_on_ok_hosts'            => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 3 AND acknowledged + in_downtime = 0 THEN 1 ELSE 0 END)',
            'services_unknown_passive_on_ok_hosts'              => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 3 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_unknown_not_checked_on_ok_hosts'          => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 3 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_ok_on_problem_hosts'                      => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 0 THEN 1 ELSE 0 END)',
            'services_ok_not_checked_on_problem_hosts'          => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 0 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_pending_on_problem_hosts'                 => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 99 THEN 1 ELSE 0 END)',
            'services_pending_not_checked_on_problem_hosts'     => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 99 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_warning_handled_on_problem_hosts'         => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 1 AND acknowledged + in_downtime > 0 THEN 1 ELSE 0 END)',
            'services_warning_unhandled_on_problem_hosts'       => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 1 AND acknowledged + in_downtime = 0 THEN 1 ELSE 0 END)',
            'services_warning_passive_on_problem_hosts'         => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 1 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_warning_not_checked_on_problem_hosts'     => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 1 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_critical_handled_on_problem_hosts'        => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 2 AND acknowledged + in_downtime > 0 THEN 1 ELSE 0 END)',
            'services_critical_unhandled_on_problem_hosts'      => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 2 AND acknowledged + in_downtime = 0 THEN 1 ELSE 0 END)',
            'services_critical_passive_on_problem_hosts'        => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 2 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_critical_not_checked_on_problem_hosts'    => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 2 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_unknown_handled_on_problem_hosts'         => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 3 AND acknowledged + in_downtime > 0 THEN 1 ELSE 0 END)',
            'services_unknown_unhandled_on_problem_hosts'       => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 3 AND acknowledged + in_downtime = 0 THEN 1 ELSE 0 END)',
            'services_unknown_passive_on_problem_hosts'         => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 3 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_unknown_not_checked_on_problem_hosts'     => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 3 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)'
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
            array(
                ''
            )
        )->join(
            array('h' => $this->prefix . 'hosts'),
            'hs.host_object_id = h.host_object_id',
            array()
        );
        $services = clone $hosts;
        $services->join(
            array('s' => $this->prefix . 'services'),
            's.host_object_id = h.host_object_id',
            array()
        )->join(
            array('so' => $this->prefix . 'objects'),
            'so.' . $this->object_id . ' = s.service_object_id AND so.is_active = 1',
            array()
        )->joinLeft(
            array('ss' => $this->prefix . 'servicestatus'),
            'so.' . $this->object_id . ' = ss.service_object_id',
            array()
        );
        $hosts->columns(array(
            'state'                         => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END',
            'acknowledged'                  => 'hs.problem_has_been_acknowledged',
            'in_downtime'                   => 'CASE WHEN (hs.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END',
            'host_state'                    => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END',
            'host_problem'                  => 'CASE WHEN COALESCE(hs.current_state, 0) = 0 THEN 0 ELSE 1 END',
            'is_passive_checked'            => 'CASE WHEN hs.active_checks_enabled = 0 AND hs.passive_checks_enabled = 1 THEN 1 ELSE 0 END',
            'is_active_checked'             => 'hs.active_checks_enabled',
            'is_processing_events'          => 'hs.event_handler_enabled',
            'is_triggering_notifications'   => 'hs.notifications_enabled',
            'is_allowed_to_flap'            => 'hs.flap_detection_enabled',
            'is_flapping'                   => 'hs.is_flapping',
            'object_type'                   => '(\'host\')'
        ));
        $services->columns(array(
            'state'                         => 'CASE WHEN ss.has_been_checked = 0 OR ss.has_been_checked IS NULL THEN 99 ELSE ss.current_state END',
            'acknowledged'                  => 'ss.problem_has_been_acknowledged',
            'in_downtime'                   => 'CASE WHEN (ss.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END',
            'host_state'                    => 'CASE WHEN hs.has_been_checked = 0 OR hs.has_been_checked IS NULL THEN 99 ELSE hs.current_state END',
            'host_problem'                  => 'CASE WHEN COALESCE(hs.current_state, 0) = 0 THEN 0 ELSE 1 END',
            'is_passive_checked'            => 'CASE WHEN ss.active_checks_enabled = 0 AND ss.passive_checks_enabled = 1 THEN 1 ELSE 0 END',
            'is_active_checked'             => 'ss.active_checks_enabled',
            'is_processing_events'          => 'ss.event_handler_enabled',
            'is_triggering_notifications'   => 'ss.notifications_enabled',
            'is_allowed_to_flap'            => 'ss.flap_detection_enabled',
            'is_flapping'                   => 'ss.is_flapping',
            'object_type'                   => '(\'service\')'
        ));
        $union = $this->db->select()->union(array($hosts, $services), Zend_Db_Select::SQL_UNION_ALL);
        $this->subHosts    = $hosts;
        $this->subServices = $services;
        $this->select->from(array('statussummary' => $union), array());
        $this->joinedVirtualTables = array(
            'services'             => true,
            'servicestatussummary' => true,
            'hoststatussummary'    => true
        );
    }

    public function whereToSql($col, $sign, $expression)
    {
        if ($col === 'so.name1') {
            $this->subServices->where('so.name1 ' . $sign . ' ?', $expression);
            return '';
            return 'sh.state_time ' . $sign . ' ' . $this->timestampForSql($this->valueToTimestamp($expression));
        } else {
            return parent::whereToSql($col, $sign, $expression);
        }
    }
}
