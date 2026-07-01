<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
            [
                'icon'      => 'dashboard',
                'label'     => t('Add Dashlet'),
                'url'       => Url::fromPath('dashboard/new-dashlet')
            ]
        );

        $tabs->addAsDropdown(
            'dashboard_settings',
            [
                'icon'      => 'dashboard',
                'label'     => t('Settings'),
                'url'       => Url::fromPath('dashboard/settings')
            ]
        );
    }
}
