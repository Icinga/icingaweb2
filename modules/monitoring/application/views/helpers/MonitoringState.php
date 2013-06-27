<?php


class Zend_View_Helper_MonitoringState extends Zend_View_Helper_Abstract
{
    private $servicestates = array('ok', 'warning', 'critical', 'unknown', 99 => 'pending', null => 'pending');
    private $hoststates = array('up', 'down', 'unreachable', 99 => 'pending', null => 'pending');

    public function monitoringState($object, $type = 'service') {
        
        if ($type === 'service') {
            return $this->servicestates[$object->service_state];
        } else if ($type === 'host') {
            return $this->hoststates[$object->host_state];
        }
    }

}

