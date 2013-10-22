<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

class StatusSummaryQuery extends StatusdatQuery
{
    public function selectBase()
    {
        $this->select()->from('hosts', array())->groupByFunction(
            'groupByStatus',
            $this
        );
    }

    public function groupByStatus(&$indices)
    {
        $hostsPending               = 0;
        $hostsUp                    = 0;
        $hostsDownHandled           = 0;
        $hostsDownUnhandled         = 0;
        $hostsUnreachableHandled    = 0;
        $hostsUnreachableUnhandled  = 0;
        $servicesPending            = 0;
        $servicesOk                 = 0;
        $servicesCriticalHandled    = 0;
        $servicesCriticalUnhandled  = 0;
        $servicesWarningHandled     = 0;
        $servicesWarningUnhandled   = 0;
        $servicesUnknownHandled     = 0;
        $servicesUnknownUnhandled   = 0;
        foreach ($indices['host'] as $hostName) {
            $host       = $this->ds->getObjectByName('host', $hostName);
            $hostStatus = $host->status;
            if ($hostStatus->has_been_checked !== '1') {
                ++$hostsPending;
            } elseif ($hostStatus->current_state === '0') {
                ++$hostsUp;
            } elseif ($hostStatus->current_state === '2') {
                if ($hostStatus->problem_has_been_acknowledged === '1'
                    || $hostStatus->scheduled_downtime_depth === '1'
                ) {
                    ++$hostsDownHandled;
                } else {
                    ++$hostsDownUnhandled;
                }
            } elseif ($hostStatus->current_state === '1') {
                if ($hostStatus->problem_has_been_acknowledged === '1'
                    || $hostStatus->scheduled_downtime_depth === '1'
                ) {
                    ++$hostsUnreachableHandled;
                } else {
                    ++$hostsUnreachableUnhandled;
                }
            }
            if ($host->services === null) {
                // Host does not have any service associated
                continue;
            }
            foreach ($host->services as $service) {
                $serviceStatus = $service->status;
                if ($serviceStatus->has_been_checked !== '1') {
                    ++$servicesPending;
                } elseif ($serviceStatus->current_state === '0') {
                    ++$servicesOk;
                } elseif ($serviceStatus->current_state === '2') {
                    if ($serviceStatus->problem_has_been_acknowledged === '1'
                        || $serviceStatus->scheduled_downtime_depth === '1'
                        || $hostStatus->current_state !== '0'
                    ) {
                        ++$servicesCriticalHandled;
                    } else {
                        ++$servicesCriticalUnhandled;
                    }
                } elseif ($serviceStatus->current_state === '1') {
                    if ($serviceStatus->problem_has_been_acknowledged === '1'
                        || $serviceStatus->scheduled_downtime_depth === '1'
                        || $hostStatus->current_state !== '0'
                    ) {
                        ++$servicesWarningHandled;
                    } else {
                        ++$servicesWarningUnhandled;
                    }
                } elseif ($serviceStatus->current_state === '3') {
                    if ($serviceStatus->problem_has_been_acknowledged === '1'
                        || $serviceStatus->scheduled_downtime_depth === '1'
                        || $hostStatus->current_state !== '0'
                    ) {
                        ++$servicesUnknownHandled;
                    } else {
                        ++$servicesUnknownUnhandled;
                    }
                }
            }
        }
        $rs = array(
            'hosts_up'                      => $hostsUp,
            'hosts_unreachable_handled'     => $hostsUnreachableHandled,
            'hosts_unreachable_unhandled'   => $hostsUnreachableUnhandled,
            'hosts_down_handled'            => $hostsDownHandled,
            'hosts_down_unhandled'          => $hostsDownUnhandled,
            'hosts_pending'                 => $hostsPending,
            'services_ok'                   => $servicesOk,
            'services_unknown_handled'      => $servicesUnknownHandled,
            'services_unknown_unhandled'    => $servicesUnknownUnhandled,
            'services_critical_handled'     => $servicesCriticalHandled,
            'services_critical_unhandled'   => $servicesCriticalUnhandled,
            'services_warning_handled'      => $servicesWarningHandled,
            'services_warning_unhandled'    => $servicesWarningUnhandled,
            'services_pending'              => $servicesPending
        );
        return array((object) array_intersect_key($rs, array_flip($this->getColumns())));
    }
}
