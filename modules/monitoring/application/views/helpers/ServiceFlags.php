<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

class Zend_View_Helper_ServiceFlags extends Zend_View_Helper_Abstract
{
    public function serviceFlags($service) {
        $icons = array();
        if (!$service->service_handled && $service->service_state > 0) {
            $icons[] = $this->view->icon('attention-alt', $this->view->translate('Unhandled'));
        }
        if ($service->service_acknowledged && !$service->service_in_downtime) {
            $icons[] = $this->view->icon('ok', $this->view->translate('Acknowledged') . (
                $service->service_last_ack ? ': ' . $service->service_last_ack : ''
            ));
        }
        if ($service->service_is_flapping) {
            $icons[] = $this->view->icon('flapping', $this->view->translate('Flapping')) ;
        }
        if (!$service->service_notifications_enabled) {
            $icons[] = $this->view->icon('bell-off-empty', $this->view->translate('Notifications Disabled'));
        }
        if ($service->service_in_downtime) {
            $icons[] = $this->view->icon('plug', $this->view->translate('In Downtime'));
        }
        if (isset($service->service_last_comment) && $service->service_last_comment !== null) {
            $icons[] = $this->view->icon(
                'comment',
                $this->view->translate('Last Comment: ') . $service->service_last_comment
            );
        }
        if (!$service->service_active_checks_enabled) {
            if (!$service->service_passive_checks_enabled) {
                $icons[] = $this->view->icon('eye-off', $this->view->translate('Active And Passive Checks Disabled'));
            } else {
                $icons[] =  $this->view->icon('eye-off', $this->view->translate('Active Checks Disabled'));
            }
        }
        return $icons;
    }
}