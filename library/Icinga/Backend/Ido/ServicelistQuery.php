<?php

namespace Icinga\Backend\Ido;
class ServicelistQuery extends Query
{
    protected $available_columns = array(
        // Host config
        'host_name'              => 'so.name1',
        'host_display_name'      => 'h.display_name',
        'host_alias'             => 'h.alias',
        'host_address'           => 'h.address',
        'host_ipv4'              => 'INET_ATON(h.address)',
       
        'host_icon_image'        => 'h.icon_image',

        // Host state
        'host_state'                  => 'hs.current_state',
        'host_output'                 => 'hs.output',
        'host_perfdata'               => 'hs.perfdata',
        'host_acknowledged'           => 'hs.problem_has_been_acknowledged',
        'host_does_active_checks'     => 'hs.active_checks_enabled',
        'host_accepts_passive_checks' => 'hs.passive_checks_enabled',
        'host_last_state_change'      => 'UNIX_TIMESTAMP(hs.last_state_change)',

        // Service config
        'service_description'    => 'so.name2',
        'service_display_name'   => 's.display_name',

        // Service state
        'current_state'          => 'ss.current_state',
        'service_state'          => 'ss.current_state',
        'service_output'         => 'ss.output',
        'service_perfdata'       => 'ss.perfdata',
        'service_acknowledged'   => 'ss.problem_has_been_acknowledged',
        'service_in_downtime'    => 'CASE WHEN (ss.scheduled_downtime_depth = 0) THEN 0 ELSE 1 END',
        'service_handled'        => 'CASE WHEN ss.problem_has_been_acknowledged + ss.scheduled_downtime_depth > 0 THEN 1 ELSE 0 END',
        'service_does_active_checks'     => 'ss.active_checks_enabled',
        'service_accepts_passive_checks' => 'ss.passive_checks_enabled',
        'service_last_state_change'      => 'UNIX_TIMESTAMP(ss.last_state_change)',

        // Service comments
        //'service_downtimes_with_info' => "IF(dt.object_id IS NULL, NULL, GROUP_CONCAT(CONCAT('[', dt.author_name, '] ', dt.comment_data) ORDER BY dt.entry_time DESC SEPARATOR '|'))",
        //'service_comments_with_info'  => "IF(co.object_id IS NULL, NULL, GROUP_CONCAT(CONCAT('[', co.author_name, '] ', co.comment_data) ORDER BY co.entry_time DESC SEPARATOR '|'))",
         // SLA Example:
         // 'sla' => "icinga_availability(so.object_id,"
         //        . " '2012-12-01 00:00:00', '2012-12-31 23:59:59')",
    );

    protected function init()
    {
        parent::init();
        if ($this->dbtype === 'oracle') {
            $this->columns['host_last_state_change'] = 
                'localts2unixts(ss.last_state_change)';
            $this->columns['service_last_state_change'] = 
                'localts2unixts(ss.last_state_change)';
        }
    }

    public function where($column, $value = null)
    {
        // Ugly temporary hack:
        if ($column === 'problems') {
            if ($value === true || $value === 'true') {
                parent::where('current_state', '-0');
            } elseif ($value === false || $value === 'false') {
                parent::where('current_state', '0');
            }
            return $this;
        }

        if ($column === 'handled') $column = 'service_handled';
        parent::where($column, $value);
        return $this;
    }

    protected function createQuery()
    {
        $query = $this->prepareServiceStatesQuery();
        if ($this->dbtype === 'mysql') {
            // $this->addServiceComments($query);
        } else {
            $this->columns['host_ipv4'] = 'h.address';
            $this->columns['service_downtimes_with_info'] = '(NULL)';
            $this->columns['service_comments_with_info'] = '(NULL)';
        }
        return $query;
    }

    protected function createCountQuery()
    {
        return $this->prepareServicesCount();
    }
}

