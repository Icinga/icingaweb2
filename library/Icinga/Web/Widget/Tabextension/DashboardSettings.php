<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget\Tabextension;

use Icinga\Web\Url;
use Icinga\Web\Widget\Tabs;

/**
 * Dashboard settings
 */
class DashboardSettings implements Tabextension
{
    /**
     * Apply this tabextension to the provided tabs
     *
     * @param Tabs $tabs The tabbar to modify
     */
    public function apply(Tabs $tabs)
    {
        $tabs->addAsDropdown(
            'dashboard_add',
            array(
                'icon'      => 'dashboard',
                'label'     => t('Add Dashlet'),
                'url'       => Url::fromPath('dashboard/new-dashlet')
            )
        );

        $tabs->addAsDropdown(
            'dashboard_settings',
            array(
                'icon'      => 'dashboard',
                'label'     => t('Settings'),
                'url'       => Url::fromPath('dashboard/settings')
            )
        );
    }
}
