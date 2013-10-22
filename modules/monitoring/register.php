<?php

use Icinga\Authentication\Manager as AuthManager;
use Icinga\Application\Icinga;
use Icinga\Module\Monitoring\DataView\StatusSummary as StatusSummaryView;
use Icinga\Web\Topbar;

if (Icinga::app()->isCli()) {
    return;
}

$request = Icinga::app()->getFrontController()->getRequest();

if (AuthManager::getInstance()->isAuthenticated()) {
    $hostSummary = StatusSummaryView::fromRequest(
        $request,
        array(
            'hosts_up',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_pending'
        )
    )->getQuery()->fetchRow();

    $serviceSummary = StatusSummaryView::fromRequest(
        $request,
        array(
            'services_ok',
            'services_critical_handled',
            'services_critical_unhandled',
            'services_warning_handled',
            'services_warning_unhandled',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_pending'
        )
    )->getQuery()->fetchRow();

    Topbar::addPartial(
        'topbar.phtml',
        'monitoring',
        array('hostSummary' => $hostSummary, 'serviceSummary' => $serviceSummary)
    );
}

?>
