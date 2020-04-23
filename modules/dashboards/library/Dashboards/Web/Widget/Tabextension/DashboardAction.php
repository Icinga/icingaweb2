<?php

namespace Icinga\Module\Dashboards\Web\Widget\Tabextension;

use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\Tabextension;
use Icinga\Web\Widget\Tabs;

/**
 * Tabextension that allows to add the current URL to a dashboard
 *
 * Displayed as a dropdown field in the tabs
 */
class DashboardAction implements Tabextension
{
    /**
     * Applies the dashboard extension to the provided tabset
     *
     * @param Tabs $tabs The tabs object to extend with
     *
     * @throws \Icinga\Exception\ProgrammingError
     */
    public function apply(Tabs $tabs)
    {
        $tabs->addAsDropdown(
            'dashboard_add',
            array(
                'icon' => 'dashboard',
                'label' => t('Add Dashlet'),
                'url' => Url::fromPath('dashboards/dashlets/new'),
                'urlParams' => array(
                    'url' => rawurlencode(Url::fromRequest()->getRelativeUrl())
                )
            )
        );

        $tabs->addAsDropdown(
            'dashboard_settings',
            array(
                'icon' => 'wrench',
                'label' => t('Settings'),
                'url' => Url::fromPath('dashboards/settings')
            )
        );
    }
}
