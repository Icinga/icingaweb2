<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Statusdat\Query;

/**
 * Handling downtime queries
 */
class DowntimeQuery extends StatusdatQuery
{
    /**
     * Column map
     * @var array
     */
    public static $mappedParameters = array(
        'downtime_author'                   => 'author',
        'downtime_comment'                  => 'comment',
        'downtime_duration'                 => 'duration',
        'downtime_end'                      => 'end_time',
        'downtime_was_started'              => 'was_started',
        'downtime_is_fixed'                 => 'fixed',
        'downtime_is_in_effect'             => 'is_in_effect',
        'downtime_trigger_time'             => 'trigger_time',
        'downtime_triggered_by_id'          => 'triggered_by_id',
        'downtime_internal_downtime_id'     => 'downtime_id',
        'downtime_scheduled_start_time'     => 'start_time',
        'host'                              => 'host_name',
        'host_name'                         => 'host_name',
        'service_host_name'                 => 'host_name'
    );

    public static $handlerParameters = array(
        'object_type'                       => 'getObjectType',
        'downtime_start'                    => 'getDowntimeStart',
        'downtime_is_flexible'              => 'getFlexibleFlag',
        'service_description'               => 'getServiceDescription'
    );

    public static $fieldTypes = array(
        'downtime_end'          => self::TIMESTAMP,
        'downtime_trigger_time' => self::TIMESTAMP,
        'downtime_start'        => self::TIMESTAMP
    );

    public function getServiceDescription(&$obj)
    {
        if (isset ($obj->service_description)) {
            return $obj->service_description;
        }
        return '';
    }


    public function getDowntimeStart(&$obj)
    {
        if ($obj->trigger_time != '0') {
            return $obj->trigger_time;
        } else {
            return $obj->start_time;
        }
    }


    public function getFlexibleFlag(&$obj)
    {
        return $obj->fixed ? 0 : 1;
    }

    public function getObjectType(&$obj)
    {
        return isset($obj->service_description) ? 'service ': 'host';
    }

    public function selectBase()
    {
        $this->select()->from("downtimes", array());
    }
}
