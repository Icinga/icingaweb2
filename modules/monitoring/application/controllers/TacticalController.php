<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

use Icinga\Module\Monitoring\Controller as MonitoringController;
use Icinga\Web\Url;

class Monitoring_TacticalController extends MonitoringController
{
    public function indexAction()
    {
        $this->getTabs()->add(
            'tactical_overview',
            array(
                'title' => $this->translate('Tactical Overview'),
                'url'   => Url::fromRequest()
            )
        )->activate('tactical_overview');

        $this->view->statusSummary = $this->backend->select()->from(
            'statusSummary',
            array(
                'hosts_up',
                'hosts_pending',
                'hosts_down',
                'hosts_down_unhandled',
                'hosts_unreachable',
                'hosts_unreachable_unhandled',

                'services_ok_on_ok_hosts',
                'services_ok_not_checked_on_ok_hosts',
                'services_pending_on_ok_hosts',
                'services_pending_not_checked_on_ok_hosts',
                'services_warning_handled_on_ok_hosts',
                'services_warning_unhandled_on_ok_hosts',
                'services_warning_passive_on_ok_hosts',
                'services_warning_not_checked_on_ok_hosts',
                'services_critical_handled_on_ok_hosts',
                'services_critical_unhandled_on_ok_hosts',
                'services_critical_passive_on_ok_hosts',
                'services_critical_not_checked_on_ok_hosts',
                'services_unknown_handled_on_ok_hosts',
                'services_unknown_unhandled_on_ok_hosts',
                'services_unknown_passive_on_ok_hosts',
                'services_unknown_not_checked_on_ok_hosts',
                'services_ok_on_problem_hosts',
                'services_ok_not_checked_on_problem_hosts',
                'services_pending_on_problem_hosts',
                'services_pending_not_checked_on_problem_hosts',
                'services_warning_handled_on_problem_hosts',
                'services_warning_unhandled_on_problem_hosts',
                'services_warning_passive_on_problem_hosts',
                'services_warning_not_checked_on_problem_hosts',
                'services_critical_handled_on_problem_hosts',
                'services_critical_unhandled_on_problem_hosts',
                'services_critical_passive_on_problem_hosts',
                'services_critical_not_checked_on_problem_hosts',
                'services_unknown_handled_on_problem_hosts',
                'services_unknown_unhandled_on_problem_hosts',
                'services_unknown_passive_on_problem_hosts',
                'services_unknown_not_checked_on_problem_hosts',

                'hosts_active',
                'hosts_passive',
                'hosts_not_checked',
                'services_active',
                'services_passive',
                'services_not_checked',
                'hosts_not_processing_event_handlers',
                'services_not_processing_event_handlers',
                'hosts_not_triggering_notifications',
                'services_not_triggering_notifications',
                'hosts_without_flap_detection',
                'services_without_flap_detection',
                'hosts_flapping',
                'services_flapping'
            )
        )->getQuery()->fetchRow();
    }
}
