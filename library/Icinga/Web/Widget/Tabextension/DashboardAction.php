<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Widget\Tabextension;

use Icinga\Web\Url;
use Icinga\Web\Widget\Tabs;

/**
 * Tabextension that allows to add the current URL to a dashboard
 *
 * Displayed as a dropdown field in the tabs
 */
class DashboardAction implements Tabextension
{
    /**
     * Applies the dashboard actions to the provided tabset
     *
     * @param   Tabs $tabs The tabs object to extend with
     */
    public function apply(Tabs $tabs)
    {
        $tabs->addAsDropdown(
            'dashboard',
            [
                'icon'      => 'dashboard',
                'label'     => t('Add To Dashboard'),
                'url'       => Url::fromPath('dashboard/new-dashlet'),
                'urlParams' => [
                    'url' => rawurlencode(Url::fromRequest()->getRelativeUrl())
                ]
            ]
        );
    }
}
