<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

class GroupsummaryQuery extends StatusdatQuery
{
    public static $mappedParameters = array(
        'hostgroup'                    => 'hostgroup_name',
        'servicegroup'                 => 'servicegroup_name'
    );

    public static $handlerParameters = array(
        'hosts_up'                      => 'getHostUpSum',
        'hosts_unreachable_handled'     => 'getHostUnreachableSum',
        'hosts_unreachable_unhandled'   => 'getHostUnreachableUnhandledSum',
        'hosts_down_handled'            => 'getHostDownSum',
        'hosts_down_unhandled'          => 'getHostDownUnhandledSum',
        'hosts_pending'                 => 'getHostPendingSum',
        'services_ok'                   => 'getServiceOkSum',
        'services_unknown_handled'      => 'getServiceUnknownSum',
        'services_unknown_unhandled'    => 'getServiceUnknownUnhandledSum',
        'services_critical_handled'     => 'getServiceCriticalSum',
        'services_critical_unhandled'   => 'getServiceCriticalUnhandledSum',
        'services_warning_handled'      => 'getServiceWarningSum',
        'services_warning_unhandled'    => 'getServiceWarningUnhandledSum',
        'services_pending'              => 'getServicePendingSum',

    );

    private function getMembers(&$obj, $hint = null)
    {
        if (!isset($obj->service) && !isset($obj->host)) {
            return null;
        }
        $memberList = isset($obj->service) ? $obj->service  : $obj->host;

        if (isset($obj->host) && $hint == 'service') {
            $result = array();
            foreach ($memberList as &$member) {
                if (isset($member->services)) {
                    $result = $result + $member->services;
                }
            }
            return $result;
        }
        return $memberList;
    }

    private function getMembersByCriteria(&$obj, $type, $namefield, $criteriaFn)
    {
        $memberList = $this->getMembers($obj, $type);
        if ($memberList === null) {
            return 0;
        }
        $ids = array();
        foreach ($memberList as $member) {
            $name = $member->$type->$namefield;
            if ($namefield === 'service_description') {
                $name .= ';' . $member->$type->host_name;
            }

            if (isset($ids[$name])) {
                continue;
            } else {
                if ($criteriaFn($member->$type)) {
                    $ids[$name] = true;
                }
            }
        }
        return count(array_keys($ids));
    }

    public function getHostUpSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'host', 'host_name', function($member) {
            return ($member->status->current_state == 0
                && $member->status->has_been_checked);
        });
    }

    public function getHostUnreachableSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'host', 'host_name', function($member) {
            return ($member->status->current_state == 2
                && $member->status->has_been_checked
                && (
                    $member->status->problem_has_been_acknowledged
                    || $member->status->scheduled_downtime_depth
                )
            );
        });
    }

    public function getHostUnreachableUnhandledSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'host', 'host_name', function($member) {
            return ($member->status->current_state == 2
                && $member->status->has_been_checked
                && !(
                    $member->status->problem_has_been_acknowledged
                    || $member->status->scheduled_downtime_depth
                )
            );
        });
    }

    public function getHostDownSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'host', 'host_name', function($member) {
            return ($member->status->current_state == 1
                && $member->status->has_been_checked
                && (
                    $member->status->problem_has_been_acknowledged
                    || $member->status->scheduled_downtime_depth
                )
            );
        });
    }

    public function getHostDownUnhandledSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'host', 'host_name', function($member) {
            return ($member->status->current_state == 1
                && $member->status->has_been_checked
                && !(
                    $member->status->problem_has_been_acknowledged
                    || $member->status->scheduled_downtime_depth
                )
            );
        });
    }

    public function getHostPendingSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'host', 'host_name', function($member) {
            return (!$member->status->has_been_checked);
        });
    }

    public function getServiceOkSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'service', 'service_description', function($member) {
            return ($member->status->current_state == 0
                && $member->status->has_been_checked);
        });
    }

    public function getServiceUnknownSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'service', 'service_description', function($member) {

            return ($member->status->current_state == 3
                && $member->status->has_been_checked
                && (
                    $member->status->problem_has_been_acknowledged
                    || $member->status->scheduled_downtime_depth
                )
            );
        });
    }

    public function getServiceUnknownUnhandledSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'service', 'service_description', function($member) {
            return ($member->status->current_state == 3
                && $member->status->has_been_checked
                && !(
                    $member->status->problem_has_been_acknowledged
                    || $member->status->scheduled_downtime_depth
                )
            );
        });
    }

    public function getServiceCriticalSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'service', 'service_description', function($member) {
            return ($member->status->current_state == 2
                && $member->status->has_been_checked
                && (
                    $member->status->problem_has_been_acknowledged
                    || $member->status->scheduled_downtime_depth
                )
            );
        });
    }

    public function getServiceCriticalUnhandledSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'service', 'service_description', function($member) {
            return ($member->status->current_state == 2
                && $member->status->has_been_checked
                && !(
                    $member->status->problem_has_been_acknowledged
                    || $member->status->scheduled_downtime_depth
                )
            );
        });
    }

    public function getServiceWarningSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'service', 'service_description', function($member) {
            return ($member->status->current_state == 1
                && $member->status->has_been_checked
                && !(
                    $member->status->problem_has_been_acknowledged
                    || $member->status->scheduled_downtime_depth
                )
            );
        });
    }

    public function getServiceWarningUnhandledSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'service', 'service_description', function($member) {
            return ($member->status->current_state == 1
                && $member->status->has_been_checked
                && (
                    $member->status->problem_has_been_acknowledged
                    || $member->status->scheduled_downtime_depth
                )
            );
        });
    }

    public function getServicePendingSum(&$obj)
    {
        return $this->getMembersByCriteria($obj, 'service', 'service_description', function($member) {
            return (!$member->status->has_been_checked);
        });
    }

    private function getTarget()
    {
        if (in_array('servicegroup', $this->getColumns())) {
            return 'servicegroups';
        }
        return 'hostgroups';
    }

    public function selectBase()
    {
        $this->select()->from($this->getTarget(), array());
    }
}
