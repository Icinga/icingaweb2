<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * @deprecated Most of these helpers are currently only used in the MultiController, which is probably obsolete
 */
class Zend_View_Helper_MonitoringState extends Zend_View_Helper_Abstract
{
    private $servicestates = array('ok', 'warning', 'critical', 'unknown', 99 => 'pending', null => 'pending');
    private $hoststates = array('up', 'down', 'unreachable', 99 => 'pending', null => 'pending');

    /**
     * @deprecated Not used anywhere.
     */
    public function monitoringState($object, $type = 'service')
    {
        if ($type === 'service') {
            return $this->servicestates[$object->service_state];
        } elseif ($type === 'host') {
            return $this->hoststates[$object->host_state];
        }
    }

    /**
     * @deprecated Not used anywhere.
     */
    public function monitoringStateById($id, $type = 'service')
    {
        if ($type === 'service') {
            return $this->servicestates[$id];
        } elseif ($type === 'host') {
            return $this->hoststates[$id];
        }
    }

    /**
     * @deprecated Monitoring colors are clustered.
     */
    public function getServiceStateColors()
    {
        return array('#44bb77', '#FFCC66', '#FF5566', '#E066FF', '#77AAFF');
    }

    /**
     * @deprecated Monitoring colors are clustered.
     */
    public function getHostStateColors()
    {
        return array('#44bb77', '#FF5566', '#E066FF', '#77AAFF');
    }

    /**
     * @deprecated The service object must know about it's possible states.
     */
    public function getServiceStateNames()
    {
        return array_values($this->servicestates);
    }

    /**
     * @deprecated The host object must know about it's possible states.
     */
    public function getHostStateNames()
    {
        return array_values($this->hoststates);
    }

    /**
     * @deprecated Not used anywhere.
     */
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

    /**
     * @deprecated Not used anywhere.
     */
    public function getStateTitle($object, $type)
    {
        return sprintf(
            '%s %s %s',
             $this->view->translate(strtoupper($this->monitoringState($object, $type))),
            $this->view->translate('since'),
            date('Y-m-d H:i:s', $object->{$type.'_last_state_change'})
        );
    }
}
