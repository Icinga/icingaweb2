<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

class ContactQuery extends StatusdatQuery
{
    public static $mappedParameters = array(
        'contact_name'                     => 'contact_name',
        'contact_alias'                    => 'alias',
        'contact_email'                    => 'email',
        'contact_pager'                    => 'pager',
        'contact_has_host_notfications'    => 'host_notifications_enabled',
        'contact_has_service_notfications' => 'service_notifications_enabled',
        'contact_can_submit_commands'      => 'can_submit_commands',
        'contact_notify_host_timeperiod'   => 'host_notification_period',
        'contact_notify_service_timeperiod'=> 'service_notification_period',
        'contact_service_notification_options' => 'service_notification_options',
        'contact_host_notification_options' => 'host_notification_options',
        'contactgroup_name'                => 'group.contactgroup_name',
        'contactgroup_alias'               => 'group.alias'
    );

    public static $handlerParameters = array(
        'contact_notify_service_recovery'  => 'getServiceRecoveryFlag',
        'contact_notify_service_warning'   => 'getServiceWarningFlag',
        'contact_notify_service_critical'  => 'getServiceCriticalFlag',
        'contact_notify_service_unknown'   => 'getServiceUnknownFlag',
        'contact_notify_service_flapping'  => 'getServiceFlappingFlag',
        'contact_notify_service_downtime'  => 'getServiceDowntimeFlag',
        'contact_notify_host_recovery'     => 'getHostRecoveryFlag',
        'contact_notify_host_down'         => 'getHostDownFlag',
        'contact_notify_host_unreachable'  => 'getHostUnreachableFlag',
        'contact_notify_host_flapping'     => 'getHostFlappingFlag',
        'contact_notify_host_downtime'     => 'getHostDowntimeFlag',
        'host_name'                        => 'getHost',
        'host'                             => 'getHost',
        'service_host_name'                => 'getHost',
        'service'                          => 'getService',
        'service_description'              => 'getService'
    );


    public function getHost(&$obj)
    {
        $result = array();
        foreach ($this->state['host'] as $values) {
            if (!isset($values->contacts)) {
                continue;
            }
            if (stripos($values->contacts, $obj->contacts) !== false) {
                $result[] = $values;
            }
        }
        return $result;
    }

    public function getService(&$obj)
    {
        $result = array();
        foreach ($this->state['service'] as  $values) {
            if (!isset($values->contacts)) {
                continue;
            }
            if (stripos($values->contact_groups, $obj->contacts) !== false) {
                $result[] = $values;
            }
        }
        return $result;
    }

    public function getServiceRecoveryFlag(&$obj)
    {
        return stripos($obj->service_notification_options, 'r') === false ? 0 : 1;
    }

    public function getServiceWarningFlag(&$obj)
    {
        return stripos($obj->service_notification_options, 'w') === false ? 0 : 1;
    }

    public function getServiceCriticalFlag(&$obj)
    {
        return stripos($obj->service_notification_options, 'c') === false ? 0 : 1;
    }

    public function getServiceUnknownFlag(&$obj)
    {
        return stripos($obj->service_notification_options, 'u') === false ? 0 : 1;
    }

    public function getServiceFlappingFlag(&$obj)
    {
        return stripos($obj->service_notification_options, 'f') === false ? 0 : 1;
    }

    public function getServiceDowntimeFlag(&$obj)
    {
        return stripos($obj->service_notification_options, 's') === false ? 0 : 1;
    }

    public function getHostRecoveryFlag(&$obj)
    {
        return stripos($obj->host_notification_options, 'r') === false ? 0 : 1;
    }

    public function getHostDownFlag(&$obj)
    {
        return stripos($obj->host_notification_options, 'd') === false ? 0 : 1;
    }

    public function getHostUnreachableFlag(&$obj)
    {
        return stripos($obj->host_notification_options, 'u') === false ? 0 : 1;
    }

    public function getHostFlappingFlag(&$obj)
    {
        return stripos($obj->host_notification_options, 'f') === false ? 0 : 1;
    }

    public function getHostDowntimeFlag(&$obj)
    {
        return strpos($obj->host_notification_options, 's') === false ? 0 : 1;
    }

    public function selectBase()
    {
        $this->state = $this->ds->getState();
        $this->select()->from("contacts", array());
    }
}
