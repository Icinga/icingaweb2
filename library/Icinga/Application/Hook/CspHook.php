<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;
use Icinga\User;
use ipl\Web\Common\Csp;

/**
 * Allow modules to add directives to the Content Security Policy (CSP)
 *
 * Icinga Web uses this hook only if strict CSP is enabled, module CSP is enabled,
 * and no custom CSP is configured.
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
     * Get the CSP directives the given user needs in addition to {@see static::getCspForAllUsers()}
     *
     * Icinga Web adds these directives to the CSP of the given user only.
     * The CSP overview on the Security page does not list them.
     *
     * @param User $user The user to get the directives for
     *
     * @return Csp Empty by default
     */
    public function getCspForUser(User $user): Csp
    {
        return new Csp();
    }

    /**
     * Get the CSP directives every user needs
     *
     * Icinga Web adds these directives to the CSP of every user
     * and lists them in the CSP overview on the Security page.
     *
     * @return Csp
     */
    abstract public function getCspForAllUsers(): Csp;
}
