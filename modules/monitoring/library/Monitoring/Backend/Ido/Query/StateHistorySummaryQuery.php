<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Backend\Ido\Query;

class StateHistorySummaryQuery extends IdoQuery
{
    protected $columnMap = array(
        'statehistory' => array(
            'day'                  => 'DATE(sh.state_time)',
            'cnt_events'           => 'COUNT(*)',
            'cnt_up'               => 'SUM(CASE WHEN sho.objecttype_id = 1 AND sh.state = 0 THEN 1 ELSE 0 END)',
            'cnt_down_hard'        => 'SUM(CASE WHEN sho.objecttype_id = 1 AND sh.state = 1 AND state_type = 1 THEN 1 ELSE 0 END)',
            'cnt_down'             => 'SUM(CASE WHEN sho.objecttype_id = 1 AND sh.state = 1 THEN 1 ELSE 0 END)',
            'cnt_unreachable_hard' => 'SUM(CASE WHEN sho.objecttype_id = 1 AND sh.state = 2 AND state_type = 1 THEN 1 ELSE 0 END)',
            'cnt_unreachable'      => 'SUM(CASE WHEN sho.objecttype_id = 1 AND sh.state = 2 THEN 1 ELSE 0 END)',
            'cnt_unknown_hard'     => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 3 AND state_type = 1 THEN 1 ELSE 0 END)',
            'cnt_unknown'          => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 3 THEN 1 ELSE 0 END)',
            'cnt_unknown_hard'     => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 3 AND state_type = 1 THEN 1 ELSE 0 END)',
            'cnt_critical'         => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 2 THEN 1 ELSE 0 END)',
            'cnt_critical_hard'    => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 2 AND state_type = 1 THEN 1 ELSE 0 END)',
            'cnt_warning'          => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 1 THEN 1 ELSE 0 END)',
            'cnt_warning_hard'     => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 1 AND state_type = 1 THEN 1 ELSE 0 END)',
            'cnt_ok'               => 'SUM(CASE WHEN sho.objecttype_id = 2 AND sh.state = 0 THEN 1 ELSE 0 END)',
        )
    );

    protected function joinBaseTables()
    {
        $this->select->from(
            array('sh' => $this->prefix . 'statehistory'),
            array()
        )->join(
            array('sho' => $this->prefix . 'objects'),
            'sh.object_id = sho.object_id AND sho.is_active = 1',
            array()
        )->where('sh.state_time >= ?', '2013-11-20 00:00:00')
        ->group('DATE(sh.state_time)');
        $this->joinedVirtualTables = array('statehistory' => true);
    }
}
