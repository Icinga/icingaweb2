<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

/**
 * Convert runtime summary data into a simple usable stdClass
 */
class Zend_View_Helper_RuntimeVariables extends Zend_View_Helper_Abstract
{
    /**
     * Create dispatch instance
     *
     * @return $this
     */
    public function runtimeVariables()
    {
        return $this;
    }

    /**
     * Create a condensed row of object data
     *
     * @param   $result      	    stdClass
     *
     * @return  stdClass            Condensed row
     */
    public function create(stdClass $result)
    {
        $out = new stdClass();
        $out->total_hosts = isset($result->total_hosts)
            ? $result->total_hosts
            : 0;
        $out->total_scheduled_hosts = isset($result->total_scheduled_hosts)
            ? $result->total_scheduled_hosts
            : 0;
        $out->total_services = isset($result->total_services)
            ? $result->total_services
            : 0;
        $out->total_scheduled_services = isset($result->total_scheduled_services)
            ? $result->total_scheduled_services
            : 0;
        $out->average_services_per_host = $out->total_hosts > 0
            ? $out->total_services / $out->total_hosts
            : 0;
        $out->average_scheduled_services_per_host = $out->total_scheduled_hosts > 0
            ? $out->total_scheduled_services / $out->total_scheduled_hosts
            : 0;

        return $out;
    }
}
