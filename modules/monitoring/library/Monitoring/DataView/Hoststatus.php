<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\DataView;

class HostStatus extends DataView
{
    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array_merge($this->getHookedColumns(), array(
            'host_acknowledged',
            'host_acknowledgement_type',
            'host_action_url',
            'host_active_checks_enabled',
            'host_active_checks_enabled_changed',
            'host_address',
            'host_address6',
            'host_alias',
            'host_check_command',
            'host_check_execution_time',
            'host_check_latency',
            'host_check_source',
            'host_check_timeperiod',
            'host_current_check_attempt',
            'host_current_notification_number',
            'host_display_name',
            'host_event_handler_enabled',
            'host_event_handler_enabled_changed',
            'host_flap_detection_enabled',
            'host_flap_detection_enabled_changed',
            'host_handled',
            'host_hard_state',
            'host_in_downtime',
            'host_ipv4',
            'host_is_flapping',
            'host_is_reachable',
            'host_last_check',
            'host_last_notification',
            'host_last_state_change',
            'host_long_output',
            'host_max_check_attempts',
            'host_modified_host_attributes',
            'host_name',
            'host_next_check',
            'host_notes_url',
            'host_notifications_enabled',
            'host_notifications_enabled_changed',
            'host_obsessing',
            'host_obsessing_changed',
            'host_output',
            'host_passive_checks_enabled',
            'host_passive_checks_enabled_changed',
            'host_percent_state_change',
            'host_perfdata',
            'host_problem',
            'host_severity',
            'host_state',
            'host_state_type',
            'host_unhandled',
            'instance_name'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getStaticFilterColumns()
    {
        return array(
            'host',
            'hostgroup', 'hostgroup_alias', 'hostgroup_name',
            'service', 'service_description', 'service_display_name',
            'servicegroup', 'servicegroup_alias', 'servicegroup_name'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchColumns($search = null)
    {
        if ($search !== null
            && (@inet_pton($search) !== false || preg_match('/^\d{1,3}\.\d{1,3}\./', $search))
        ) {
            return array('host', 'host_address', 'host_address6');
        } else {
            if ($this->connection->isIcinga2()) {
                return array('host', 'host_display_name');
            } else {
                return array('host', 'host_display_name', 'host_alias');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSortRules()
    {
        return array(
            'host_display_name' => array(
                'order' => self::SORT_ASC
            ),
            'host_severity' => array(
                'columns' => array(
                    'host_severity',
                    'host_last_state_change DESC',
                    'host_display_name ASC'
                ),
                'order' => self::SORT_DESC
            ),
            'host_address' => array(
                'columns' => array(
                    'host_ipv4'
                ),
                'order' => self::SORT_ASC
            ),
            'host_last_state_change' => array(
                'order' => self::SORT_DESC
            )
        );
    }
}
