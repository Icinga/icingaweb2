<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

class ContactgroupQuery extends StatusdatQuery
{
    public static $mappedParameters = array(
        'contactgroup_name'                    => 'contactgroup_name',
        'contactgroup_alias'                   => 'alias',
        'contact_name'                         => 'contact.contact_name',
        'contact_alias'                        => 'contact.alias',
        'contact_email'                        => 'contact.email',
        'contact_pager'                        => 'contact.pager',
        'contact_has_host_notfications'        => 'contact.host_notifications_enabled',
        'contact_has_service_notfications'     => 'contact.service_notifications_enabled',
        'contact_can_submit_commands'          => 'contact.can_submit_commands',
        'contact_notify_host_timeperiod'       => 'contact.host_notification_period',
        'contact_notify_service_timeperiod'    => 'contact.service_notification_period',
        'contact_service_notification_options' => 'contact.service_notification_options',
        'contact_host_notification_options'    => 'contact.host_notification_options',
    );

    public static $handlerParameters = array(
        'host_name'                            => 'getHosts',
        'host'                                 => 'getHosts',
        'service_host_name'                    => 'getHosts',
        'service'                              => 'getService',
        'service_description'                  => 'getService'
    );

    public function getHosts(&$obj)
    {
        $result = array();
        foreach ($this->state['host'] as $values) {
            if (stripos($values->contact_groups, $obj->contactgroup_name) !== false) {
                $result[] = $values;
            }
        }
        return $result;
    }

    public function getService(&$obj)
    {
        $result = array();
        foreach ($this->state['service'] as  $values) {
            if (stripos($values->contact_groups, $obj->contactgroup_name) !== false) {
                $result[] = $values;
            }
        }
        return $result;
    }

    public function selectBase()
    {
        $this->state = $this->ds->getState();
        return $this->select()->from("contactgroups", array());

    }
}
