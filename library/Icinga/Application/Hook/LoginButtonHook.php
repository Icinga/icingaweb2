<?php
/* Icinga Web 2 | (c) 2026 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;
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
    use Essentials {
        register as protected parentRegister;
    }

    protected static function getHookName(): string
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

    /**
     * Register a hook provider
     *
     * Always runs the hook, without permission check. Latter makes no sense on the login page.
     */
    public static function register(): void
    {
        static::parentRegister(true);
    }
}
