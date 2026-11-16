<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Icinga\Authentication\LoginButton;

/**
 * Hook for adding custom buttons to the login page
 *
 * Extend this class to display additional buttons on the Icinga Web login page,
 * e.g. for alternative authentication flows such as SSO.
 * The buttons are rendered below the login form. The login page shows no form
 * and only the buttons if at least one button is rendered and no enabled
 * authentication backend other than `external` is configured.
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
     * Get the buttons to display on the login page
     *
     * Implement this method to return any number of {@link LoginButton} instances.
     * Each button is rendered in the order provided, below the standard login form if there is one.
     *
     * @return LoginButton[]
     */
    abstract public function getButtons(): array;
}
