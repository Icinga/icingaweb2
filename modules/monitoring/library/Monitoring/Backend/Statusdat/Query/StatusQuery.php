<?php
/**
 * Created by JetBrains PhpStorm.
 * User: moja
 * Date: 7/17/13
 * Time: 1:29 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

use Icinga\Protocol\Statusdat;
use Icinga\Protocol\Statusdat\IReader;
use Icinga\Exception;

class StatusQuery extends StatusdatQuery
{
    /**
     * @var array
     */
    public static $mappedParameters = array(
        'host'                                  => 'host.host_name',
        'host_name'                             => 'host.host_name',
        'host_display_name'                     => 'host.host_name',
        'host_alias'                            => 'host.alias',
        'host_address'                          => 'host.address',
        'host_icon_image'                       => 'host.icon_image',
        'host_action_url'                       => 'host.action_url',
        'host_notes_url'                        => 'host.notes_url',
        'host_output'                           => 'host.status.plugin_output',
        'host_long_output'                      => 'host.status.long_plugin_output',
        'host_perfdata'                         => 'host.status.performance_data',
        'host_check_source'                     => 'host.status.check_source',
        'host_acknowledged'                     => 'host.status.problem_has_been_acknowledged',
        'host_last_state_change'                => 'host.status.last_state_change',
        'host_last_hard_state'                  => 'host.status.last_hard_state',
        'host_last_hard_state_change'           => 'host.status.last_hard_state_change',
        'host_check_command'                    => 'host.status.check_command',
        'host_last_check'                       => 'host.status.last_check',
        'host_next_check'                       => 'host.status.next_check',
        'host_check_execution_time'             => 'host.status.check_execution_time',
        'host_check_latency'                    => 'host.status.check_latency',
        'host_notifications_enabled'            => 'host.status.notifications_enabled',
        'host_last_time_up'                     => 'host.status.last_time_up',
        'host_last_time_down'                   => 'host.status.last_time_down',
        'host_last_time_unreachable'            => 'host.status.last_time_unreachable',
        'host_current_check_attempt'            => 'host.status.current_attempt',
        'host_max_check_attempts'               => 'host.status.max_attempts',
        'host_check_type'                       => 'host.status.check_type',
        'host_state_type'                       => 'host.status.state_type',
        'host_last_notification'                => 'host.status.last_notification',
        'host_next_notification'                => 'host.status.next_notification',
        'host_no_more_notifications'            => 'host.status.no_more_notifications',
        'host_problem_has_been_acknowledged'    => 'host.status.problem_has_been_acknowledged',
        'host_acknowledgement_type'             => 'host.status.acknowledgement_type',
        'host_current_notification_number'      => 'host.status.current_notification_number',
        'host_passive_checks_enabled'           => 'host.status.passive_checks_enabled',
        'host_active_checks_enabled'            => 'host.status.active_checks_enabled',
        'host_event_handler_enabled'            => 'host.status.event_handler_enabled',
        'host_flap_detection_enabled'           => 'host.status.flap_detection_enabled',
        'host_is_flapping'                      => 'host.status.is_flapping',
        'host_percent_state_change'             => 'host.status.percent_state_change',
        'host_scheduled_downtime_depth'         => 'host.status.scheduled_downtime_depth',
        'host_failure_prediction_enabled'       => 'host.status.failure_prediction_enabled',
        'host_process_performance_data'         => 'host.status.process_performance_data',
        'host_obsessing'                        => 'host.status.obsess_over_host',
        'host_modified_host_attributes'         => 'host.status.modified_host_attributes',
        'host_event_handler'                    => 'host.status.event_handler',
        'host_check_command'                    => 'host.status.check_command',
        'host_normal_check_interval'            => 'host.status.normal_check_interval',
        'host_retry_check_interval'             => 'host.status.retry_check_interval',
        'host_check_timeperiod_object_id'       => 'host.status.check_timeperiod_object_id',
        'host_status_update_time'               => 'host.status.status_update_time',

        'service_host_name'                     => 'service.host_name',
        'service'                               => 'service.service_description',
        'service_description'                   => 'service.service_description',
        'service_display_name'                  => 'service.service_description',
        'service_icon_image'                    => 'service.icon_image',
        'service_action_url'                    => 'service.action_url',
        'service_notes_url'                     => 'service.notes_url',
        'service_state_type'                    => 'service.status.state_type',
        'service_output'                        => 'service.status.output',
        'service_long_output'                   => 'service.status.long_output',
        'service_perfdata'                      => 'service.status.perfdata',
        'service_check_source'                  => 'service.status.check_source',
        'service_acknowledged'                  => 'service.status.problem_has_been_acknowledged',
        'service_last_state_change'             => 'service.status.last_state_change',
        'service_check_command'                 => 'service.status.check_command',
        'service_last_time_ok'                  => 'service.status.last_time_ok',
        'service_last_time_warning'             => 'service.status.last_time_warning',
        'service_last_time_critical'            => 'service.status.last_time_critical',
        'service_last_time_unknown'             => 'service.status.last_time_unknown',
        'service_current_check_attempt'         => 'service.status.current_check_attempt',
        'service_max_check_attempts'            => 'service.status.max_check_attempts',
        'service_last_check'                    => 'service.status.last_check',
        'service_next_check'                    => 'service.status.next_check',
        'service_check_type'                    => 'service.status.check_type',
        'service_last_hard_state_change'        => 'service.status.last_hard_state_change',
        'service_last_hard_state'               => 'service.status.last_hard_state',
        'service_last_notification'             => 'service.status.last_notification',
        'service_next_notification'             => 'service.status.next_notification',
        'service_no_more_notifications'         => 'service.status.no_more_notifications',
        'service_notifications_enabled'         => 'service.status.notifications_enabled',
        'service_problem_has_been_acknowledged' => 'service.status.problem_has_been_acknowledged',
        'service_acknowledgement_type'          => 'service.status.acknowledgement_type',
        'service_current_notification_number'   => 'service.status.current_notification_number',
        'service_passive_checks_enabled'        => 'service.status.passive_checks_enabled',
        'service_active_checks_enabled'         => 'service.status.active_checks_enabled',
        'service_event_handler_enabled'         => 'service.status.event_handler_enabled',
        'service_flap_detection_enabled'        => 'service.status.flap_detection_enabled',
        'service_is_flapping'                   => 'service.status.is_flapping',
        'service_percent_state_change'          => 'service.status.percent_state_change',
        'service_check_latency'                 => 'service.status.latency',
        'service_check_execution_time'          => 'service.status.execution_time',
        'service_scheduled_downtime_depth'      => 'service.status.scheduled_downtime_depth',
        'service_failure_prediction_enabled'    => 'service.status.failure_prediction_enabled',
        'service_process_performance_data'      => 'service.status.process_performance_data',
        'service_obsessing'                     => 'service.status.obsess_over_service',
        'service_modified_service_attributes'   => 'service.status.modified_service_attributes',
        'service_event_handler'                 => 'service.status.event_handler',
        'service_check_command'                 => 'service.status.check_command',
        'service_normal_check_interval'         => 'service.status.normal_check_interval',
        'service_retry_check_interval'          => 'service.status.retry_check_interval',
        'service_check_timeperiod_object_id'    => 'service.status.check_timeperiod_object_id',
        'service_status_update_time'            => 'service.status.status_update_time',
        'hostgroup'                             => 'host.group',
        'servicegroup'                          => 'service.group'
    );

    /**
     * @var mixed
     */
    private $state;

    /**
     * @var array
     */
    public static $handlerParameters = array(
        'host_ipv4'                     => 'getAddress',
        'host_unhandled_service_count'  => 'getNrOfUnhandledServices',
        'host_last_comment'             => 'getLastComment',
        'service_last_comment'          => 'getLastComment',
        'host_state'                    => 'getStateForHost',
        'host_hard_state'               => 'getHardStateForHost',
        'host_handled'                  => 'isHandledForHost',
        'host_unhandled'                => 'isHostUnhandled',
        'host_severity'                 => 'getSeverityForHost',
        'host_in_downtime'              => 'isInDowntimeForHost',
        'host_problem'                  => 'isProblemForHost',
        'host_attempt'                  => 'getAttemptStringForHost',
        'service_state'                 => 'getState',
        'service_hard_state'            => 'getHardState',
        'service_handled'               => 'isHandled',
        'service_unhandled'             => 'isUnhandled',
        'service_severity'              => 'getSeverity',
        'service_in_downtime'           => 'isInDowntime',
        'service_problem'               => 'isProblem',
        'service_attempt'               => 'getAttemptString',
    );

    public static $fieldTypes = array(
        'host_last_state_change'            => self::TIMESTAMP,
        'host_last_hard_state_change'       => self::TIMESTAMP,
        'host_last_check'                   => self::TIMESTAMP,
        'host_next_check'                   => self::TIMESTAMP,
        'host_last_time_up'                 => self::TIMESTAMP,
        'host_last_time_down'               => self::TIMESTAMP,
        'host_last_time_unreachable'        => self::TIMESTAMP,
        'host_status_update_time'           => self::TIMESTAMP,
        'service_last_state_change'         => self::TIMESTAMP,
        'service_last_hard_state_change'    => self::TIMESTAMP,
        'service_last_check'                => self::TIMESTAMP,
        'service_next_check'                => self::TIMESTAMP,
        'service_last_time_ok'              => self::TIMESTAMP,
        'service_last_time_warning'         => self::TIMESTAMP,
        'service_last_time_critical'        => self::TIMESTAMP,
        'service_last_time_unknown'         => self::TIMESTAMP,
        'service_status_update_time'        => self::TIMESTAMP
    );

    public function selectBase()
    {
        $target = $this->getTarget();
        $this->select()->from($target."s", array());
    }

    public function getAttemptString(&$obj)
    {
        return $obj->status->current_attempt . '/' . $obj->status->max_attempts;
    }

    public function isProblem(&$obj)
    {
        return $obj->status->current_state > 0 ? 1 : 0;
    }

    public function isInDowntime(&$obj)
    {
        return $obj->status->scheduled_downtime_depth > 0 ? 1 : 0;
    }

    public function getAddress(&$obj)
    {
        return inet_pton($obj->host->address);
    }

    public function getState(&$obj)
    {
        if (!$obj->status->has_been_checked) {
            return 99;
        }
        return $obj->status->current_state;
    }

    public function getHardState(&$obj)
    {
        if (!$obj->status->has_been_checked) {
            return 99;
        } else {
            if ($obj->status->state_type == 1) {
                return $this->status->current_state;
            } else {
                return $this->status->last_hard_state;
            }
        }
    }


    public function getSeverity(&$host)
    {
        $status = $host->status;
        $severity = 0;

        if (!$status->has_been_checked) {
            $severity += 16;
        } elseif($status->current_state == 0) {
            return $severity;
        } elseif ($status->current_state == 1) {
            $severity += 32;
        } elseif ($status->current_state == 2) {
            $severity += 64;
        } else {
            $severity += 256;
        }

        if ($status->problem_has_been_acknowledged == 1) {
            $severity += 2;
        } elseif ($status->scheduled_downtime_depth > 0) {
            $severity += 1;
        } else {
            $severity += 4;
        }

        return $severity;
    }

    public function isHandled(&$host)
    {
        return ($host->status->current_state == 0 ||
            $host->status->problem_has_been_acknowledged == 1 ||
            $host->status->scheduled_downtime_depth > 0) ? 1 : 0;
    }

    public function isUnhandled(&$hostOrService)
    {
        return +!$this->isHandled($hostOrService);
    }

    public function getNrOfUnhandledServices(&$obj)
    {
        $host = &$obj->host;
        $ct = 0;
        if (!isset($host->services)) {
            return $ct;
        }
        foreach ($host->services as &$service) {
            if ($service->status->current_state > 0
                && $service->status->problem_has_been_acknowledged == 0
                && $service->status->scheduled_downtime_depth == 0) {
                $ct++;
            }
        }
        return $ct;
    }

    public function getLastComment(&$host)
    {
        if (!isset($host->comment) || empty($host->comment)) {
            return null;
        }
        $comment = end($host->comment);
        return $comment->comment_id;
    }


    /**
     * @param $item
     * @return null
     */
    public function getHost(&$item)
    {
        if (!isset($this->state['service'][$item->host_name])) {
            return null;
        }
        if (!isset($this->state['host'][$item->host_name])) {
            return null;
        }
        return $this->state['host'][$item->host_name];
    }


    private function getTarget()
    {
        foreach ($this->getColumns() as $column) {
            if (preg_match("/^service/",$column)) {
                return "service";
            }
        }

        return "host";
    }


    public function getStateForHost(&$obj)
    {
        return $this->getState($obj->host);
    }

    public function getHardStateForHost(&$obj)
    {
        return $this->getHardState($obj->host);
    }

    public function isHandledForHost(&$obj)
    {
        return $this->isHandled($obj->host);
    }

    public function isHostUnhandled(&$obj)
    {
        return $this->isUnhandled($obj->host);
    }

    public function getSeverityForHost(&$obj)
    {
        return $this->getSeverity($obj->host);
    }

    public function isInDowntimeForHost(&$obj)
    {
        return $this->isInDowntime($obj->host);
    }

    public function isProblemForHost(&$obj)
    {
        return $this->isProblem($obj->host);
    }

    public function getAttemptStringForHost(&$obj)
    {
        return $this->getAttemptStringForHost($obj->host);
    }


}
