<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

class Zend_View_Helper_RenderServicePerfdata extends Zend_View_Helper_Abstract
{
    private static $RENDERMAP = array(
        "check_local_disk" =>  array("self::renderDiskPie")
    );

    public function renderServicePerfdata($service)
    {
        if (isset(self::$RENDERMAP[$service->check_command])) {
            $fn = self::$RENDERMAP[$service->check_command];
            $fn($service);
        }
    }

    public static function renderDiskPie($service) {
        $perfdata = $service->performance_data;
        if(!$perfdata)
            return "";

    }
}
