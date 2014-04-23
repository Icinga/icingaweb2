<?php

class Zend_View_Helper_MonitoringState extends Zend_View_Helper_Abstract
{
    private $servicestates = array('ok', 'warning', 'critical', 'unknown', 99 => 'pending', null => 'pending');
    private $hoststates = array('up', 'down', 'unreachable', 99 => 'pending', null => 'pending');

    public function monitoringState($object, $type = 'service')
    {
        if ($type === 'service') {
            return $this->servicestates[$object->service_state];
        } elseif ($type === 'host') {
            return $this->hoststates[$object->host_state];
        }
    }

    public function getServiceStateColors()
    {
        return array('#44bb77', '#FFCC66', '#FF5566', '#E066FF', '#77AAFF');
    }

    public function getHostStateColors()
    {
        return array('#44bb77', '#FF5566', '#E066FF', '#77AAFF');
    }

    public function getServiceStateNames()
    {
        return array_values($this->servicestates);
    }

    public function getHostStateNames()
    {
        return array_values($this->hoststates);
    }

    public function getStateFlags($object, $type = 'service')
    {
        $state_classes = array();
        if ($type === 'host') {
            $state_classes[] = $this->monitoringState($object, "host");
            if ($object->host_acknowledged || $object->host_in_downtime) {
                $state_classes[] = 'handled';
            }
            if ($object->host_last_state_change > (time() - 600)) {
                $state_classes[] = 'new';
            }
        } else {
            $state_classes[] = $this->monitoringState($object, "service");
            if ($object->service_acknowledged || $object->service_in_downtime) {
                $state_classes[] = 'handled';
            }
            if ($object->service_last_state_change > (time() - 600)) {
                $state_classes[] = 'new';
            }
        }

        return $state_classes;
    }

    public function getStateTitle($object, $type)
    {
        return strtoupper($this->monitoringState($object, $type))
        . ' since '
        . date('Y-m-d H:i:s', $object->{$type.'_last_state_change'});
    }
}
