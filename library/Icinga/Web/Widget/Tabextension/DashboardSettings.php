<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
                'icon'      => 'img/icons/dashboard.png',
                'title'     => t('Add To Dashboard'),
                'url'       => Url::fromPath('dashboard/new-dashboard')
            )
        );

        $tabs->addAsDropdown(
            'dashboard_settings',
            array(
            'icon'      => 'img/icons/dashboard.png',
            'title'     => t('Settings'),
            'url'       => Url::fromPath('dashboard/settings')
            )
        );
    }
}