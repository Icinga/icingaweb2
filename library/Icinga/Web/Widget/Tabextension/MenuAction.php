<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Widget\Tabextension;

use Icinga\Web\Url;
use Icinga\Web\Widget\Tabs;

/**
 * Tabextension that allows to add the current URL as menu entry
 *
 * Displayed as a dropdown field in the tabs
 */
class MenuAction implements Tabextension
{
    /**
     * Applies the menu actions to the provided tabset
     *
     * @param   Tabs $tabs The tabs object to extend with
     */
    public function apply(Tabs $tabs)
    {
        $tabs->addAsDropdown(
            'menu-entry',
            [
                'icon'      => 'menu',
                'label'     => t('Add To Menu'),
                'url'       => Url::fromPath('navigation/add'),
                'urlParams' => [
                    'url' => rawurlencode(Url::fromRequest()->getRelativeUrl())
                ]
            ]
        );
    }
}
