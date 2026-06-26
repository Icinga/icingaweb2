<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Security\Csp\Loader;

use DirectoryIterator;
use Icinga\Application\Config;
use Icinga\Security\Csp\AttributedCsp;
use Icinga\Security\Csp\Reason\DashboardCspReason;
use Icinga\User;
use Icinga\Web\Url;
use Icinga\Web\Widget\Dashboard;
use ipl\Web\Common\Csp;

/**
 * This loader is responsible for loading CSP directives for external URLs in dashboard panes.
 * It iterates through all dashboard panes and checks if any dashlets have an external URL.
 * If an external URL is found, it adds a CSP directive for the URL's host and port.
 * The CSP directive allows the iframe to be embedded on the page.
 */
class DashboardCspLoader implements CspLoader
{
    public function loadForUser(User $user): array
    {
        $dashboard = new Dashboard();
        $dashboard->setUser($user);
        $dashboard->load();

        $result = [];

        /** @var Dashboard\Pane $pane */
        foreach ($dashboard->getPanes() as $pane) {
            /** @var Dashboard\Dashlet $dashlet */
            foreach ($pane->getDashlets() as $dashlet) {
                $url = $dashlet->getUrl();
                if ($url === null) {
                    continue;
                }

                $urlString = $url->isExternal()
                    ? $url->getAbsoluteUrl()
                    : $url->getParam('url');

                if ($urlString === null || filter_var($urlString, FILTER_VALIDATE_URL) === false) {
                    continue;
                }

                $absoluteUrl = Url::fromPath($urlString);
                $cspUrl = $absoluteUrl->getScheme() . '://' . $absoluteUrl->getHost();
                if (($port = $absoluteUrl->getPort()) !== null) {
                    $cspUrl .= ':' . $port;
                }

                $csp = new Csp();
                $csp->add('frame-src', $cspUrl);
                $result[] = new AttributedCsp($csp, new DashboardCspReason($dashboard, $pane, $dashlet));
            }
        }

        return $result;
    }

    public function loadForAllUsers(): array
    {
        $result = [];
        $dashboardsDir = Config::resolvePath('dashboards');
        if (! is_dir($dashboardsDir)) {
            return $result;
        }

        foreach (new DirectoryIterator($dashboardsDir) as $dir) {
            if ($dir->isDot() || ! $dir->isDir()) {
                continue;
            }

            $user = new User($dir->getFilename());
            $result = array_merge($result, $this->loadForUser($user));
        }

        return $result;
    }
}
