<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use Icinga\Module\Monitoring\Controller;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\MenuAction;

class TacticalController extends Controller
{
    public function indexAction()
    {
        $this->setAutorefreshInterval(15);
        $this->getTabs()->add(
            'tactical_overview',
            array(
                'title' => $this->translate(
                    'Show an overview of all hosts and services, their current'
                    . ' states and monitoring feature utilisation'
                ),
                'label' => $this->translate('Tactical Overview'),
                'url'   => Url::fromRequest()
            )
        )->extend(new DashboardAction())->extend(new MenuAction())->activate('tactical_overview');
        $stats = $this->backend->select()->from(
            'statussummary',
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
        );
        $this->applyRestriction('monitoring/filter/objects', $stats);
        $this->view->statusSummary = $stats->fetchRow();
    }
}
