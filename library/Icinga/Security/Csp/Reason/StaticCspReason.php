<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Security\Csp\Reason;

/**
 * A hardcoded CSP reason.
 * Useful for testing or providing a static CSP configuration.
 */
readonly class StaticCspReason implements CspReason
{
    /**
     * @param string $name The name to display for CSP reason
     */
    public function __construct(
        public string $name,
    ) {
    }
}
