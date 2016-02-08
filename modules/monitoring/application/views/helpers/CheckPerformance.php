<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

/**
 * Convert check summary data into a simple usable stdClass
 */
class Zend_View_Helper_CheckPerformance extends Zend_View_Helper_Abstract
{
    /**
     * Create dispatch instance
     *
     * @return $this
     */
    public function checkPerformance()
    {
        return $this;
    }

    /**
     * Create a condensed row of object data
     *
     * @param   array $results      Array of stdClass
     *
     * @return  stdClass            Condensed row
     */
    public function create(array $results)
    {
        $out = new stdClass();
        $out->host_passive_count = 0;
        $out->host_passive_latency_avg = 0;
        $out->host_passive_execution_avg = 0;
        $out->service_passive_count = 0;
        $out->service_passive_latency_avg = 0;
        $out->service_passive_execution_avg = 0;
        $out->service_active_count = 0;
        $out->service_active_latency_avg = 0;
        $out->service_active_execution_avg = 0;
        $out->host_active_count = 0;
        $out->host_active_latency_avg = 0;
        $out->host_active_execution_avg = 0;

        foreach ($results as $row) {
            $key = $row->object_type . '_' . $row->check_type . '_';
            $out->{$key . 'count'} = $row->object_count;
            $out->{$key . 'latency_avg'} = $row->latency / $row->object_count;
            $out->{$key . 'execution_avg'} = $row->execution_time / $row->object_count;
        }
        return $out;
    }
}
