<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;
use Icinga\User;
use ipl\Web\Common\Csp;

/**
 * Allow modules to provide custom Content-Security-Policy policies.
 * This hook is only used if the CSP header is enabled.
 */
abstract class CspHook
{
    use HookEssentials;

    final protected static function getHookName(): string
    {
        return 'Csp';
    }

    /**
     * Always run this hook
     *
     * Handling of CSP is a system-level feature, so this hook should run for every
     * request, regardless of a user's permissions.
     *
     * @return bool
     */
    protected static function isAlwaysRun(): bool
    {
        return true;
    }

    /**
     * Get the CSP directives for a module
     *
     * @param User $user The user to generate the CSP for
     *
     * @return Csp A CSP instance with the required policies, this instance will
     * be merged with all other requested directives.
     */
    abstract public function getCspForUser(User $user): Csp;

    /**
     * Get the CSP directives for a module for all users
     *
     * It should contain all required policies that are required for any user,
     * not only directives that are non user specific.
     *
     * @return Csp A CSP instance with the required policies, this instance will
     * be merged with all other requested directives.
     */
    abstract public function getCspForAllUsers(): Csp;
}
