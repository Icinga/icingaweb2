<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use Icinga\Chart\Donut;
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
                'hosts_down_handled',
                'hosts_down_unhandled',
                'hosts_unreachable_handled',
                'hosts_unreachable_unhandled',
                'hosts_pending',
                'hosts_not_checked',

                'services_ok',
                'services_warning_handled',
                'services_warning_unhandled',
                'services_critical_handled',
                'services_critical_unhandled',
                'services_unknown_handled',
                'services_unknown_unhandled',
                'services_pending',
                'services_not_checked',
            )
        );
        $this->applyRestriction('monitoring/filter/objects', $stats);

        $this->setupFilterControl($stats, null, ['host', 'service'], ['format']);
        $this->view->setHelperFunction('filteredUrl', function ($path, array $params) {
            $filter = clone $this->view->filterEditor->getFilter();
            foreach ($params as $column => $value) {
                $filter = $filter->andFilter($filter->where($column, $value));
            }

            return $this->view->url($path)->setQueryString($filter->toQueryString());
        });

        $this->handleFormatRequest($stats);
        $summary = $stats->fetchRow();

        $hostSummaryChart = new Donut();
        $hostSummaryChart
            ->addSlice($summary->hosts_up, array('class' => 'slice-state-ok'))
            ->addSlice($summary->hosts_down_handled, array('class' => 'slice-state-critical-handled'))
            ->addSlice($summary->hosts_down_unhandled, array('class' => 'slice-state-critical'))
            ->addSlice($summary->hosts_unreachable_handled, array('class' => 'slice-state-unreachable-handled'))
            ->addSlice($summary->hosts_unreachable_unhandled, array('class' => 'slice-state-unreachable'))
            ->addSlice($summary->hosts_pending, array('class' => 'slice-state-pending'))
            ->addSlice($summary->hosts_not_checked, array('class' => 'slice-state-not-checked'))
            ->setLabelBig($summary->hosts_down_unhandled)
            ->setLabelBigEyeCatching($summary->hosts_down_unhandled > 0)
            ->setLabelSmall($this->translate('Hosts Down'));

        $serviceSummaryChart = new Donut();
        $serviceSummaryChart
            ->addSlice($summary->services_ok, array('class' => 'slice-state-ok'))
            ->addSlice($summary->services_warning_handled, array('class' => 'slice-state-warning-handled'))
            ->addSlice($summary->services_warning_unhandled, array('class' => 'slice-state-warning'))
            ->addSlice($summary->services_critical_handled, array('class' => 'slice-state-critical-handled'))
            ->addSlice($summary->services_critical_unhandled, array('class' => 'slice-state-critical'))
            ->addSlice($summary->services_unknown_handled, array('class' => 'slice-state-unknown-handled'))
            ->addSlice($summary->services_unknown_unhandled, array('class' => 'slice-state-unknown'))
            ->addSlice($summary->services_pending, array('class' => 'slice-state-pending'))
            ->addSlice($summary->services_not_checked, array('class' => 'slice-state-not-checked'))
            ->setLabelBig($summary->services_critical_unhandled)
            ->setLabelBigEyeCatching($summary->services_critical_unhandled > 0)
            ->setLabelSmall($this->translate('Services Critical'));

        $this->view->hostStatusSummaryChart = $hostSummaryChart
            ->setLabelBigUrl($this->view->filteredUrl(
                'monitoring/list/hosts',
                array(
                    'host_state' => 1,
                    'host_handled' => 0,
                    'sort' => 'host_last_check',
                    'dir' => 'asc'
                )
            ))
            ->render();
        $this->view->serviceStatusSummaryChart = $serviceSummaryChart
            ->setLabelBigUrl($this->view->filteredUrl(
                'monitoring/list/services',
                array(
                    'service_state' => 2,
                    'service_handled' => 0,
                    'sort' => 'service_last_check',
                    'dir' => 'asc'
                )
            ))
            ->render();
        $this->view->statusSummary = $summary;
    }
}
