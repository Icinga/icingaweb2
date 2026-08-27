<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Widget\Tabextension;

use Icinga\Application\ClassLoader;
use Icinga\Application\Hook\DashboardActionHook;
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
        // Built once and shared by every destination below: what is being added is the view the user is
        // looking at, so a destination free to name its own would be one free to add the wrong thing.
        $urlParams = ['url' => rawurlencode(Url::fromRequest()->getRelativeUrl())];

        $tabs->addAsDropdown(
            'dashboard',
            [
                'icon'      => 'dashboard',
                'label'     => t('Add To Dashboard'),
                'url'       => Url::fromPath('dashboard/new-dashlet'),
                'urlParams' => $urlParams
            ]
        );

        // A module implementing dashboards of its own offers itself here rather than being unreachable from
        // the very views its dashlets would show. Icinga Web's own entry stays first: it is the one
        // destination that is always present.
        foreach (DashboardActionHook::all() as $hook) {
            /** @var DashboardActionHook $hook */
            $properties = [
                'icon'      => $hook->getIcon(),
                'label'     => $hook->getLabel(),
                'url'       => $hook->getUrl(),
                'urlParams' => $urlParams
            ];

            if ($hook->opensInModal()) {
                // The pair every link Icinga Web opens in a modal carries: the first says what to do with the
                // response, the second keeps the loader from treating the click as ordinary navigation.
                $properties['tagParams'] = [
                    'data-icinga-modal'   => true,
                    'data-no-icinga-ajax' => true
                ];
            }

            $tabs->addAsDropdown('dashboard-' . $this->providerOf($hook), $properties);
        }
    }

    /**
     * Get the name a hook's entry is keyed by
     *
     * The providing module, so the key is stable across requests and says in the markup where the entry came
     * from. A hook outside a module namespace has no such name and falls back to a digest of its class, which
     * is unique — all the key has to be.
     *
     * @param DashboardActionHook $hook
     *
     * @return string
     */
    private function providerOf(DashboardActionHook $hook): string
    {
        $class = get_class($hook);

        return ClassLoader::classBelongsToModule($class)
            ? ClassLoader::extractModuleName($class)
            : substr(sha1($class), 0, 8);
    }
}
