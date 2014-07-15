<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\DataView;

class Groupsummary extends DataView
{
    /**
     * Retrieve columns provided by this view
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'servicegroup',
            'hostgroup',
            'hosts_up',
            'hosts_unreachable',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_down',
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_pending',
            'services_ok',
            'services_unknown',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_critical',
            'services_critical_handled',
            'services_critical_unhandled',
            'services_warning',
            'services_warning_handled',
            'services_warning_unhandled',
            'services_pending'
        );
    }

    public function getSortRules()
    {
        if (in_array('servicegroup', $this->getQuery()->getColumns())) {
            return array(
                'servicegroup' => array(
                    'order' => self::SORT_ASC
                )
            );
        }
        return array(
            'hostgroup' => array(
                'order' => self::SORT_ASC
            )
        );
    }
}
