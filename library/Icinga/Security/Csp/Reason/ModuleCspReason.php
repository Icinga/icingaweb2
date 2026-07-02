<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Security\Csp\Reason;

/**
 * The reason for a set of CSP directives is that a module has requested them.
 */
readonly class ModuleCspReason implements CspReason
{
    /**
     * @param string $module The module to load the CSP directive for
     */
    public function __construct(
        public string $module,
    ) {
    }
}
