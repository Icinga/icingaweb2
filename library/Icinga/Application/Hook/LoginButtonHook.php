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
     * Get all registered implementations
     *
     * @return static[]
     */
    public static function all(): array
    {
        return Hook::all('LoginButton');
    }

    /**
     * Register the class as a LoginButton hook implementation
     *
     * Call this method on your implementation during module initialization to make Icinga Web aware of your hook.
     */
    public static function register(): void
    {
        Hook::register('LoginButton', static::class, static::class, true);
    }
}
