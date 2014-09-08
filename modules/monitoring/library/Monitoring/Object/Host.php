<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Object;

use Icinga\Module\Monitoring\DataView\HostStatus;
use Icinga\Data\Db\DbQuery;

class Host extends MonitoredObject
{
    public $type   = 'host';
    public $prefix = 'host_';

    protected function applyObjectFilter(DbQuery $query)
    {
        return $query->where('host_name', $this->host_name);
    }

    public function populate()
    {
        $this->fetchComments()
            ->fetchHostgroups()
            ->fetchContacts()
            ->fetchContactGroups()
            ->fetchCustomvars()
            ->fetchDowntimes();
    }

    protected function getProperties()
    {
        $this->view = HostStatus::fromParams(array('backend' => null), array(
            'host_name',
            'host_alias',
            'host_address',
            'host_state',
            'host_state_type',
            'host_handled',
            'host_in_downtime',
            'host_acknowledged',
            'host_last_state_change',
            'host_last_notification',
            'host_last_check',
            'host_next_check',
            'host_check_execution_time',
            'host_check_latency',
            'host_check_source',
            'host_output',
            'host_long_output',
            'host_check_command',
            'host_perfdata',
            'host_passive_checks_enabled',
            'host_passive_checks_enabled_changed',
            'host_obsessing',
            'host_obsessing_changed',
            'host_notifications_enabled',
            'host_notifications_enabled_changed',
            'host_event_handler_enabled',
            'host_event_handler_enabled_changed',
            'host_flap_detection_enabled',
            'host_flap_detection_enabled_changed',
            'host_active_checks_enabled',
            'host_active_checks_enabled_changed',
            'host_current_check_attempt',
            'host_max_check_attempts',
            'host_current_notification_number',
            'host_percent_state_change',
            'host_is_flapping',
            'host_action_url',
            'host_notes_url',
            'host_modified_host_attributes',
            'host_problem'
        ))->where('host_name', $this->params->get('host'));
        return $this->view->getQuery()->fetchRow();
    }
}
