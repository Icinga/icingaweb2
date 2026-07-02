<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Security\Csp\Loader;

use Icinga\Security\Csp\AttributedCsp;
use Icinga\User;

/**
 * Interface for CSP loaders.
 * A loader is responsible for loading CSP directives from a specific source.
 */
interface CspLoader
{
    /**
     * Load the CSP directives from the source for all users
     *
     * @return AttributedCsp[]
     */
    public function loadForAllUsers(): array;

    /**
     * Load the CSP directives for a specific user
     *
     * @param User $user the user to load the CSP directives for
     *
     * @return AttributedCsp[]
     */
    public function loadForUser(User $user): array;
}
