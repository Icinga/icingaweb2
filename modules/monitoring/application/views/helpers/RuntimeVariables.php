<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Convert runtime summary data into a simple usable stdClass
 */
class Zend_View_Helper_RuntimeVariables extends Zend_View_Helper_Abstract
{
    /**
     * Create dispatch instance
     *
     * @return self
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
        $out->total_hosts = $result->total_hosts;
        $out->total_scheduled_hosts = $result->total_scheduled_hosts;
        $out->total_services = $result->total_services;
        $out->total_scheduled_services = $result->total_scheduled_services;
        $out->average_services_per_host = $result->total_services / $result->total_hosts;
        $out->average_scheduled_services_per_host = $result->total_scheduled_services / $result->total_scheduled_hosts;

        return $out;
    }
}
