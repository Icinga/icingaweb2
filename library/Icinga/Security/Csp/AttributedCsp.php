<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Security\Csp;

use Icinga\Security\Csp\Reason\CspReason;
use ipl\Web\Common\Csp;

/**
 * A CSP directive attributed to a specific source via a {@see CspReason}
 */
readonly class AttributedCsp
{
    /**
     * @param Csp $csp The CSP directive
     * @param CspReason $reason The reason for the CSP directive to exist
     */
    public function __construct(
        public Csp $csp,
        public CspReason $reason,
    ) {
    }
}
