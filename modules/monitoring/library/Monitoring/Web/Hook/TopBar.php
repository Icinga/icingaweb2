<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Web\Hook;

use Icinga\Web\Hook\TopBar as IcingaTopBar;
use Icinga\Module\Monitoring\DataView\StatusSummary as StatusSummaryView;
use Icinga\Web\Request;
use Zend_View;

/**
 * Render status summary into the topbar of icinga
 */
class TopBar implements IcingaTopBar
{
    /**
     * Function to generate top bar content
     *
     * @param   Request $request
     * @param   Zend_View $view
     *
     * @return  string
     */
    public function getHtml($request, $view)
    {
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

        return $view->partial(
            'layout/topbar.phtml',
            'monitoring',
            array(
                'hostSummary'       => $hostSummary,
                'serviceSummary'    => $serviceSummary
            )
        );
    }
}
