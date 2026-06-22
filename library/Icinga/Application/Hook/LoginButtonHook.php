<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Icinga\Authentication\LoginButton;

/**
 * Hook for adding custom buttons below the login form
 *
 * Extend this class to display additional buttons on the Icinga Web login page,
 * e.g. for alternative authentication flows such as SSO.
 * To register your implementation, call `YourLoginButtons::register()` during module initialization.
 */
abstract class LoginButtonHook
{
    use HookEssentials;

    /**
     * Always runs without a permission check
     *
     * Login button hooks are intended to render on the login page, where
     * permissions are not applicable.
     *
     * @return bool
     */
    protected static function isAlwaysRun(): bool
    {
        return true;
    }

    final protected static function getHookName(): string
    {
        return 'LoginButton';
    }

    /**
     * Get the buttons to display below the login form
     *
     * Implement this method to return any number of {@link LoginButton} instances.
     * Each button is rendered below the standard login form in the order provided.
     *
     * @return LoginButton[]
     */
    abstract public function getButtons(): array;
}
