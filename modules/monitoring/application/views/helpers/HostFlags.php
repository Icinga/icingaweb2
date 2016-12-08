<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

class Zend_View_Helper_HostFlags extends Zend_View_Helper_Abstract
{
    public function hostFlags($host)
    {
        $icons = array();
        if (! $host->host_handled && $host->host_state > 0) {
            $icons[] = $this->view->icon('attention-alt', $this->view->translate('Unhandled'));
        }
        if ($host->host_acknowledged) {
            $icons[] = $this->view->icon('ok', $this->view->translate('Acknowledged'));
        }
        if ($host->host_is_flapping) {
            $icons[] = $this->view->icon('flapping', $this->view->translate('Flapping'));
        }
        if (! $host->host_notifications_enabled) {
            $icons[] = $this->view->icon('bell-off-empty', $this->view->translate('Notifications Disabled'));
        }
        if ($host->host_in_downtime) {
            $icons[] = $this->view->icon('plug', $this->view->translate('In Downtime'));
        }
        if (! $host->host_active_checks_enabled) {
            if (! $host->host_passive_checks_enabled) {
                $icons[] = $this->view->icon('eye-off', $this->view->translate('Active And Passive Checks Disabled'));
            } else {
                $icons[] = $this->view->icon('eye-off', $this->view->translate('Active Checks Disabled'));
            }
        }
        return implode(' ', $icons);
    }
}
