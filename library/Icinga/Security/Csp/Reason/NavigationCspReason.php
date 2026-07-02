<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Security\Csp\Reason;

/**
 * The reason for a CSP is a custom user-defined navigation item.
 * The item can be bound to a specific user or shared.
 */
readonly class NavigationCspReason implements CspReason
{
    /**
     * @param string $type The type of the navigation item
     * @param array $typeConfiguration The configuration of the navigation item type
     * @param ?string $parent The parent navigation item, if any
     * @param string $name The name of the navigation item
     * @param bool $isShared Whether the navigation item is shared
     * @param ?string $username The username of the user owning the navigation item
     */
    public function __construct(
        public string $type,
        public array $typeConfiguration,
        public ?string $parent,
        public string $name,
        public bool $isShared,
        public ?string $username,
    ) {
    }
}
