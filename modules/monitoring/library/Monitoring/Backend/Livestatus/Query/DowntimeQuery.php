<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Backend\Livestatus\Query;

use Icinga\Protocol\Livestatus\Query;
// TODO: still VERRRRY ugly
class DowntimeQuery extends Query
{
    protected $table = 'downtimes';

    protected $filter_flags = array(
        'downtime_is_flexible'      => '! fixed',
        'downtime_is_in_effect'     => 'fixed | ! fixed',  // just true
    );

    protected $available_columns = array(
        'downtime_author'          => 'author',
        'downtime_comment'         => 'comment',
        'downtime_entry_time'      => 'entry_time',
        'downtime_is_fixed'        => 'fixed',
        'downtime_is_flexible'     => array('fixed'),
        'downtime_triggered_by_id' => 'triggered_by',
        'downtime_scheduled_start' => 'start_time',    // ??
        'downtime_scheduled_end'   => 'end_time', // ??
        'downtime_start'           => 'start_time',
        'downtime_end'             => 'end_time',
        'downtime_duration'        => 'duration',
        'downtime_is_in_effect'    => array('fixed'),
        'downtime_internal_id'     => 'id',
        'downtime_host'            => 'host_name', // #7278, #7279
        'host'                     => 'host_name',
        'downtime_service'         => 'service_description',
        'service'                  => 'service_description', // #7278, #7279
        'downtime_objecttype'      => array('is_service'),
        'downtime_host_state'      => 'host_state',
        'downtime_service_state'   => 'service_state'
    );

    public function combineResult_downtime_objecttype(& $row, & $res)
    {
        return $res['is_service'] ? 'service' : 'host';
    }

}
