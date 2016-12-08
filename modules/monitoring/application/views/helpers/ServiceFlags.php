<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

class Zend_View_Helper_ServiceFlags extends Zend_View_Helper_Abstract
{
    public function serviceFlags($service)
    {
        $icons = array();
        if (! $service->service_handled && $service->service_state > 0) {
            $icons[] = $this->view->icon('attention-alt', $this->view->translate('Unhandled'));
        }
        if ($service->service_acknowledged) {
            $icons[] = $this->view->icon('ok', $this->view->translate('Acknowledged'));
        }
        if ($service->service_is_flapping) {
            $icons[] = $this->view->icon('flapping', $this->view->translate('Flapping'));
        }
        if (! $service->service_notifications_enabled) {
            $icons[] = $this->view->icon('bell-off-empty', $this->view->translate('Notifications Disabled'));
        }
        if ($service->service_in_downtime) {
            $icons[] = $this->view->icon('plug', $this->view->translate('In Downtime'));
        }
        if (! $service->service_active_checks_enabled) {
            if (! $service->service_passive_checks_enabled) {
                $icons[] = $this->view->icon('eye-off', $this->view->translate('Active And Passive Checks Disabled'));
            } else {
                $icons[] = $this->view->icon('eye-off', $this->view->translate('Active Checks Disabled'));
            }
        }
        return implode(' ', $icons);
    }
}
