<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

use Zend_Db_Expr;
use Zend_Db_Select;
use Icinga\Data\Filter\Filter;

/**
 * Query for host and service status summary
 */
class StatussummaryQuery extends IdoQuery
{
    /**
     * {@inheritdoc}
     */
    protected $columnMap = array(
        'hoststatussummary' => array(
            'hosts_total'                           => 'SUM(object_type = \'host\')',
            'hosts_up'                              => 'SUM(object_type = \'host\' AND state = 0)',
            'hosts_up_not_checked'                  => 'SUM(object_type = \'host\' AND state = 0 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'hosts_pending'                         => 'SUM(object_type = \'host\' AND state = 99)',
            'hosts_pending_not_checked'             => 'SUM(object_type = \'host\' AND state = 99 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'hosts_down'                            => 'SUM(object_type = \'host\' AND state = 1)',
            'hosts_down_handled'                    => 'SUM(object_type = \'host\' AND state = 1 AND handled > 0)',
            'hosts_down_unhandled'                  => 'SUM(object_type = \'host\' AND state = 1 AND handled = 0)',
            'hosts_down_passive'                    => 'SUM(object_type = \'host\' AND state = 1 AND is_passive_checked = 1)',
            'hosts_down_not_checked'                => 'SUM(object_type = \'host\' AND state = 1 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'hosts_unreachable'                     => 'SUM(object_type = \'host\' AND state = 2)',
            'hosts_unreachable_handled'             => 'SUM(object_type = \'host\' AND state = 2 AND handled > 0)',
            'hosts_unreachable_unhandled'           => 'SUM(object_type = \'host\' AND state = 2 AND handled = 0)',
            'hosts_unreachable_passive'             => 'SUM(object_type = \'host\' AND state = 2 AND is_passive_checked = 1)',
            'hosts_unreachable_not_checked'         => 'SUM(object_type = \'host\' AND state = 2 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'hosts_active'                          => 'SUM(object_type = \'host\' AND is_active_checked = 1)',
            'hosts_passive'                         => 'SUM(object_type = \'host\' AND is_passive_checked = 1)',
            'hosts_not_checked'                     => 'SUM(object_type = \'host\' AND is_active_checked = 0 AND is_passive_checked = 0)',
            'hosts_not_processing_event_handlers'   => 'SUM(object_type = \'host\' AND is_processing_events = 0)',
            'hosts_not_triggering_notifications'    => 'SUM(object_type = \'host\' AND is_triggering_notifications = 0)',
            'hosts_without_flap_detection'          => 'SUM(object_type = \'host\' AND is_allowed_to_flap = 0)',
            'hosts_flapping'                        => 'SUM(object_type = \'host\' AND is_flapping = 1)'
        ),
        'servicestatussummary' => array(
            'services_total'                            => 'SUM(object_type = \'service\')',
            'services_problem'                          => 'SUM(object_type = \'service\' AND state > 0)',
            'services_problem_handled'                  => 'SUM(object_type = \'service\' AND state > 0 AND handled + host_problem > 0)',
            'services_problem_unhandled'                => 'SUM(object_type = \'service\' AND state > 0 AND handled + host_problem = 0)',
            'services_ok'                               => 'SUM(object_type = \'service\' AND state = 0)',
            'services_ok_not_checked'                   => 'SUM(object_type = \'service\' AND state = 0 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_pending'                          => 'SUM(object_type = \'service\' AND state = 99)',
            'services_pending_not_checked'              => 'SUM(object_type = \'service\' AND state = 99 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_warning'                          => 'SUM(object_type = \'service\' AND state = 1)',
            'services_warning_handled'                  => 'SUM(object_type = \'service\' AND state = 1 AND handled + host_problem > 0)',
            'services_warning_unhandled'                => 'SUM(object_type = \'service\' AND state = 1 AND handled + host_problem = 0)',
            'services_warning_passive'                  => 'SUM(object_type = \'service\' AND state = 1 AND is_passive_checked = 1)',
            'services_warning_not_checked'              => 'SUM(object_type = \'service\' AND state = 1 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_critical'                         => 'SUM(object_type = \'service\' AND state = 2)',
            'services_critical_handled'                 => 'SUM(object_type = \'service\' AND state = 2 AND handled + host_problem > 0)',
            'services_critical_unhandled'               => 'SUM(object_type = \'service\' AND state = 2 AND handled + host_problem = 0)',
            'services_critical_passive'                 => 'SUM(object_type = \'service\' AND state = 2 AND is_passive_checked = 1)',
            'services_critical_not_checked'             => 'SUM(object_type = \'service\' AND state = 2 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_unknown'                          => 'SUM(object_type = \'service\' AND state = 3)',
            'services_unknown_handled'                  => 'SUM(object_type = \'service\' AND state = 3 AND handled + host_problem > 0)',
            'services_unknown_unhandled'                => 'SUM(object_type = \'service\' AND state = 3 AND handled + host_problem = 0)',
            'services_unknown_passive'                  => 'SUM(object_type = \'service\' AND state = 3 AND is_passive_checked = 1)',
            'services_unknown_not_checked'              => 'SUM(object_type = \'service\' AND state = 3 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_active'                           => 'SUM(object_type = \'service\' AND is_active_checked = 1)',
            'services_passive'                          => 'SUM(object_type = \'service\' AND is_passive_checked = 1)',
            'services_not_checked'                      => 'SUM(object_type = \'service\' AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_not_processing_event_handlers'    => 'SUM(object_type = \'service\' AND is_processing_events = 0)',
            'services_not_triggering_notifications'     => 'SUM(object_type = \'service\' AND is_triggering_notifications = 0)',
            'services_without_flap_detection'           => 'SUM(object_type = \'service\' AND is_allowed_to_flap = 0)',
            'services_flapping'                         => 'SUM(object_type = \'service\' AND is_flapping = 1)',

/*
NOTE: in case you might wonder, please see #7303. As a quickfix I did:

:%s/(host_state = 0 OR host_state = 99)/host_state != 1 AND host_state != 2/g
:%s/(host_state = 1 OR host_state = 2)/host_state != 0 AND host_state != 99/g

We have to find a better solution here.

*/
            'services_ok_on_ok_hosts'                           => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 0)',
            'services_ok_not_checked_on_ok_hosts'               => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 0 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_pending_on_ok_hosts'                      => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 99)',
            'services_pending_not_checked_on_ok_hosts'          => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 99 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_warning_handled_on_ok_hosts'              => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 1 AND handled > 0)',
            'services_warning_unhandled_on_ok_hosts'            => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 1 AND handled = 0)',
            'services_warning_passive_on_ok_hosts'              => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 1 AND is_passive_checked = 1)',
            'services_warning_not_checked_on_ok_hosts'          => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 1 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_critical_handled_on_ok_hosts'             => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 2 AND handled > 0)',
            'services_critical_unhandled_on_ok_hosts'           => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 2 AND handled = 0)',
            'services_critical_passive_on_ok_hosts'             => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 2 AND is_passive_checked = 1)',
            'services_critical_not_checked_on_ok_hosts'         => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 2 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_unknown_handled_on_ok_hosts'              => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 3 AND handled > 0)',
            'services_unknown_unhandled_on_ok_hosts'            => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 3 AND handled = 0)',
            'services_unknown_passive_on_ok_hosts'              => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 3 AND is_passive_checked = 1)',
            'services_unknown_not_checked_on_ok_hosts'          => 'SUM(object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 3 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_ok_on_problem_hosts'                      => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 0)',
            'services_ok_not_checked_on_problem_hosts'          => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 0 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_pending_on_problem_hosts'                 => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 99)',
            'services_pending_not_checked_on_problem_hosts'     => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 99 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_warning_handled_on_problem_hosts'         => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 1 AND handled > 0)',
            'services_warning_unhandled_on_problem_hosts'       => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 1 AND handled = 0)',
            'services_warning_passive_on_problem_hosts'         => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 1 AND is_passive_checked = 1)',
            'services_warning_not_checked_on_problem_hosts'     => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 1 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_critical_handled_on_problem_hosts'        => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 2 AND handled > 0)',
            'services_critical_unhandled_on_problem_hosts'      => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 2 AND handled = 0)',
            'services_critical_passive_on_problem_hosts'        => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 2 AND is_passive_checked = 1)',
            'services_critical_not_checked_on_problem_hosts'    => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 2 AND is_active_checked = 0 AND is_passive_checked = 0)',
            'services_unknown_handled_on_problem_hosts'         => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 3 AND handled > 0)',
            'services_unknown_unhandled_on_problem_hosts'       => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 3 AND handled = 0)',
            'services_unknown_passive_on_problem_hosts'         => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 3 AND is_passive_checked = 1)',
            'services_unknown_not_checked_on_problem_hosts'     => 'SUM(object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 3 AND is_active_checked = 0 AND is_passive_checked = 0)'
        )
    );

    /**
     * The union
     *
     * @var Zend_Db_Select
     */
    protected $summaryQuery;

    /**
     * Subqueries used for the summary query
     *
     * @var IdoQuery[]
     */
    protected $subQueries = array();

    /**
     * {@inheritdoc}
     */
    public function allowsCustomVars()
    {
        foreach ($this->subQueries as $query) {
            if (! $query->allowsCustomVars()) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(Filter $filter)
    {
        foreach ($this->subQueries as $sub) {
            $sub->applyFilter(clone $filter);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function joinBaseTables()
    {
        // TODO(el): Allow to switch between hard and soft states
        $hosts = $this->createSubQuery(
            'Hoststatus',
            array(
                'handled'                       => 'host_handled',
                'host_problem',
                'host_state'                    => new Zend_Db_Expr('NULL'),
                'is_active_checked'             => 'host_active_checks_enabled',
                'is_allowed_to_flap'            => 'host_flap_detection_enabled',
                'is_flapping'                   => 'host_is_flapping',
                'is_passive_checked'            => 'host_is_passive_checked',
                'is_processing_events'          => 'host_event_handler_enabled',
                'is_triggering_notifications'   => 'host_notifications_enabled',
                'object_type',
                'severity'                      => 'host_severity',
                'state_change'                  => 'host_last_state_change',
                'state'                         => 'host_state'
            )
        );
        $this->subQueries[] = $hosts;
        $services = $this->createSubQuery(
            'Servicestatus',
            array(
                'handled'                       => 'service_handled',
                'host_problem',
                'host_state'                    => 'host_hard_state',
                'is_active_checked'             => 'service_active_checks_enabled',
                'is_allowed_to_flap'            => 'service_flap_detection_enabled',
                'is_flapping'                   => 'service_is_flapping',
                'is_passive_checked'            => 'service_is_passive_checked',
                'is_processing_events'          => 'service_event_handler_enabled',
                'is_triggering_notifications'   => 'service_notifications_enabled',
                'object_type',
                'severity'                      => 'service_severity',
                'state_change'                  => 'service_last_state_change',
                'state'                         => 'service_state'
            )
        );
        $this->subQueries[] = $services;
        $this->summaryQuery = $this->db->select()->union(array($hosts, $services), Zend_Db_Select::SQL_UNION_ALL);
        $this->select->from(array('statussummary' => $this->summaryQuery), array());
        $this->joinedVirtualTables['hoststatussummary'] = true;
        $this->joinedVirtualTables['servicestatussummary'] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function order($columnOrAlias, $dir = null)
    {
        if (! $this->hasAliasName($columnOrAlias)) {
            foreach ($this->subQueries as $sub) {
                $sub->requireColumn($columnOrAlias);
            }
        }
        return parent::order($columnOrAlias, $dir);
    }

    /**
     * {@inheritdoc}
     */
    public function where($condition, $value = null)
    {
        $this->requireColumn($condition);
        foreach ($this->subQueries as $sub) {
            $sub->where($condition, $value);
        }
        return $this;
    }
}
