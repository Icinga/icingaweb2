<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Security\Csp\Reason;

use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Widget\Dashboard\Dashlet;
use Icinga\Web\Widget\Dashboard\Pane;

/**
 * This set of CSP directives is for a dashlet in a dashboard pane.
 */
readonly class DashboardCspReason implements CspReason
{
    /**
     * @param Dashboard $dashboard The dashboard to load the CSP directive for
     * @param Pane $pane The pane that contains the dashlet
     * @param Dashlet $dashlet The dashlet to load the CSP directive for
     */
    public function __construct(
        public Dashboard $dashboard,
        public Pane $pane,
        public Dashlet $dashlet,
    ) {
    }
}
