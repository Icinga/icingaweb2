<?php

namespace Icinga\Module\Monitoring\Object;

use Icinga\Module\Monitoring\DataView\ServiceStatus;
use Icinga\Data\Db\Query;

class Service extends AbstractObject
{
    public $type   = 'service';
    public $prefix = 'service_';
    private $view  = null;

    protected function applyObjectFilter(Query $query)
    {
        return $query->where('service_host_name', $this->host_name)
                     ->where('service_description', $this->service_description);
    }

    public function populate()
    {
        $this->fetchComments()
            ->fetchServicegroups()
            ->fetchContacts()
            ->fetchContactGroups()
            ->fetchCustomvars()
            ->fetchDowntimes();
    }

    protected function getProperties()
    {
        $this->view = ServiceStatus::fromRequest($this->request, array(
            'host_name',
            'host_state',
            'host_state_type',
            'host_last_state_change',
            'host_address',
            'host_problem',
            'host_handled',
            'service_description',
            'service_display_name',
            'service_state',
            'service_in_downtime',
            'service_acknowledged',
            'service_handled',
            'service_unhandled',
            'service_output',
            'service_last_state_change',
            'service_icon_image',
            'service_long_output',
            'service_is_flapping',
            'service_state_type',
            'service_severity',
            'service_last_check',
            'service_notifications_enabled',
            'service_notifications_enabled_changed',
            'service_action_url',
            'service_notes_url',
            'service_last_check',
            'service_next_check',
            'service_attempt',
            'service_last_notification',
            'service_check_command',
            'service_check_source',
            'service_current_notification_number',
            'host_icon_image',
            'host_acknowledged',
            'host_output',
            'host_long_output',
            'host_in_downtime',
            'host_is_flapping',
            'host_last_check',
            'host_notifications_enabled',
            'host_unhandled_service_count',
            'host_action_url',
            'host_notes_url',
            'host_last_comment',
            'host_display_name',
            'host_alias',
            'host_ipv4',
            'host_severity',
            'host_perfdata',
            'host_active_checks_enabled',
            'host_passive_checks_enabled',
            'host_last_hard_state',
            'host_last_hard_state_change',
            'host_last_time_up',
            'host_last_time_down',
            'host_last_time_unreachable',
            'host_modified_host_attributes',
            'host',
            'service',
            'service_hard_state',
            'service_problem',
            'service_perfdata',
            'service_active_checks_enabled',
            'service_active_checks_enabled_changed',
            'service_passive_checks_enabled',
            'service_passive_checks_enabled_changed',
            'service_last_hard_state',
            'service_last_hard_state_change',
            'service_last_time_ok',
            'service_last_time_warning',
            'service_last_time_critical',
            'service_last_time_unknown',
            'service_current_check_attempt',
            'service_max_check_attempts',
            'service_obsessing',
            'service_obsessing_changed',
            'service_event_handler_enabled',
            'service_event_handler_enabled_changed',
            'service_flap_detection_enabled',
            'service_flap_detection_enabled_changed',
            'service_modified_service_attributes',
        ));
        return $this->view->getQuery()->fetchRow();
    }
}
