<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\DataView;

class StatusSummary extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'hosts_up',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_pending',
            'services_ok',
            'services_critical_handled',
            'services_critical_unhandled',
            'services_warning_handled',
            'services_warning_unhandled',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_pending'
        );
    }
}
