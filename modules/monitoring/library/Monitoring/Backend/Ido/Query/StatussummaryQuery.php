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
            'hosts_total'                           => 'SUM(CASE WHEN object_type = \'host\' THEN 1 ELSE 0 END)',
            'hosts_up'                              => 'SUM(CASE WHEN object_type = \'host\' AND state = 0 THEN 1 ELSE 0 END)',
            'hosts_up_not_checked'                  => 'SUM(CASE WHEN object_type = \'host\' AND state = 0 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'hosts_pending'                         => 'SUM(CASE WHEN object_type = \'host\' AND state = 99 THEN 1 ELSE 0 END)',
            'hosts_pending_not_checked'             => 'SUM(CASE WHEN object_type = \'host\' AND state = 99 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'hosts_down'                            => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 THEN 1 ELSE 0 END)',
            'hosts_down_handled'                    => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND handled > 0 THEN 1 ELSE 0 END)',
            'hosts_down_unhandled'                  => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND handled = 0 THEN 1 ELSE 0 END)',
            'hosts_down_passive'                    => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'hosts_down_not_checked'                => 'SUM(CASE WHEN object_type = \'host\' AND state = 1 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'hosts_unreachable'                     => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 THEN 1 ELSE 0 END)',
            'hosts_unreachable_handled'             => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 AND handled > 0 THEN 1 ELSE 0 END)',
            'hosts_unreachable_unhandled'           => 'SUM(CASE WHEN object_type = \'host\' AND state = 2 AND handled = 0 THEN 1 ELSE 0 END)',
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
            'services_problem_handled'                  => 'SUM(CASE WHEN object_type = \'service\' AND state > 0 AND handled + host_problem > 0 THEN 1 ELSE 0 END)',
            'services_problem_unhandled'                => 'SUM(CASE WHEN object_type = \'service\' AND state > 0 AND handled + host_problem = 0 THEN 1 ELSE 0 END)',
            'services_ok'                               => 'SUM(CASE WHEN object_type = \'service\' AND state = 0 THEN 1 ELSE 0 END)',
            'services_ok_not_checked'                   => 'SUM(CASE WHEN object_type = \'service\' AND state = 0 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_pending'                          => 'SUM(CASE WHEN object_type = \'service\' AND state = 99 THEN 1 ELSE 0 END)',
            'services_pending_not_checked'              => 'SUM(CASE WHEN object_type = \'service\' AND state = 99 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_warning'                          => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 THEN 1 ELSE 0 END)',
            'services_warning_handled'                  => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND handled + host_problem > 0 THEN 1 ELSE 0 END)',
            'services_warning_unhandled'                => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND handled + host_problem = 0 THEN 1 ELSE 0 END)',
            'services_warning_passive'                  => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_warning_not_checked'              => 'SUM(CASE WHEN object_type = \'service\' AND state = 1 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_critical'                         => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 THEN 1 ELSE 0 END)',
            'services_critical_handled'                 => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND handled + host_problem > 0 THEN 1 ELSE 0 END)',
            'services_critical_unhandled'               => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND handled + host_problem = 0 THEN 1 ELSE 0 END)',
            'services_critical_passive'                 => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_critical_not_checked'             => 'SUM(CASE WHEN object_type = \'service\' AND state = 2 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_unknown'                          => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 THEN 1 ELSE 0 END)',
            'services_unknown_handled'                  => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND handled + host_problem > 0 THEN 1 ELSE 0 END)',
            'services_unknown_unhandled'                => 'SUM(CASE WHEN object_type = \'service\' AND state = 3 AND handled + host_problem = 0 THEN 1 ELSE 0 END)',
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
            'services_warning_handled_on_ok_hosts'              => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 1 AND handled > 0 THEN 1 ELSE 0 END)',
            'services_warning_unhandled_on_ok_hosts'            => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 1 AND handled = 0 THEN 1 ELSE 0 END)',
            'services_warning_passive_on_ok_hosts'              => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 1 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_warning_not_checked_on_ok_hosts'          => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 1 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_critical_handled_on_ok_hosts'             => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 2 AND handled > 0 THEN 1 ELSE 0 END)',
            'services_critical_unhandled_on_ok_hosts'           => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 2 AND handled = 0 THEN 1 ELSE 0 END)',
            'services_critical_passive_on_ok_hosts'             => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 2 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_critical_not_checked_on_ok_hosts'         => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 2 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_unknown_handled_on_ok_hosts'              => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 3 AND handled > 0 THEN 1 ELSE 0 END)',
            'services_unknown_unhandled_on_ok_hosts'            => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 3 AND handled = 0 THEN 1 ELSE 0 END)',
            'services_unknown_passive_on_ok_hosts'              => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 3 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_unknown_not_checked_on_ok_hosts'          => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 1 AND host_state != 2 AND state = 3 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_ok_on_problem_hosts'                      => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 0 THEN 1 ELSE 0 END)',
            'services_ok_not_checked_on_problem_hosts'          => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 0 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_pending_on_problem_hosts'                 => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 99 THEN 1 ELSE 0 END)',
            'services_pending_not_checked_on_problem_hosts'     => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 99 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_warning_handled_on_problem_hosts'         => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 1 AND handled > 0 THEN 1 ELSE 0 END)',
            'services_warning_unhandled_on_problem_hosts'       => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 1 AND handled = 0 THEN 1 ELSE 0 END)',
            'services_warning_passive_on_problem_hosts'         => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 1 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_warning_not_checked_on_problem_hosts'     => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 1 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_critical_handled_on_problem_hosts'        => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 2 AND handled > 0 THEN 1 ELSE 0 END)',
            'services_critical_unhandled_on_problem_hosts'      => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 2 AND handled = 0 THEN 1 ELSE 0 END)',
            'services_critical_passive_on_problem_hosts'        => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 2 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_critical_not_checked_on_problem_hosts'    => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 2 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)',
            'services_unknown_handled_on_problem_hosts'         => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 3 AND handled > 0 THEN 1 ELSE 0 END)',
            'services_unknown_unhandled_on_problem_hosts'       => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 3 AND handled = 0 THEN 1 ELSE 0 END)',
            'services_unknown_passive_on_problem_hosts'         => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 3 AND is_passive_checked = 1 THEN 1 ELSE 0 END)',
            'services_unknown_not_checked_on_problem_hosts'     => 'SUM(CASE WHEN object_type = \'service\' AND host_state != 0 AND host_state != 99 AND state = 3 AND is_active_checked = 0 AND is_passive_checked = 0 THEN 1 ELSE 0 END)'
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
