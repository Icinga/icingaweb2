<?php

namespace Icinga\Application\Hook;

use Icinga\User;
use Icinga\Web\Hook;
use Icinga\Application\Logger;
use Throwable;

/**
 * Icinga Web Authentication Hook base class
 *
 * This hook can be used to authenticate the user in a third party application.
 * Extend this class if you want to perform arbitrary actions during the login and logout.
 */
abstract class AuthenticationHook
{
    use Essentials;

    /**
     * Name of the hook
     */
    const NAME = 'authentication';

    protected static function getHookName(): string
    {
        return static::NAME;
    }

    /**
     * Triggered after login in Icinga Web and when calling login action even if already authenticated in Icinga Web
     *
     * @param User $user
     */
    public function onLogin(User $user)
    {
    }

    /**
     * Triggered after Icinga Web authenticates a user with the current session
     *
     * @param User $user
     */
    public function onAuthFromSession(User $user): void
    {
    }

    /**
     * Triggered before logout from Icinga Web
     *
     * @param User $user
     */
    public function onLogout(User $user)
    {
    }

    /**
     * Call the onAuthFromSession() method of all registered {@link AuthenticationHook}s
     *
     * @param User $user
     */
    public static function triggerAuthFromSession(User $user): void
    {
        /** @var static $hook */
        foreach (Hook::all(self::NAME) as $hook) {
            try {
                $hook->onAuthFromSession($user);
            } catch (Throwable $e) {
                // Avoid error propagation if a hook failed in a third party application
                Logger::error($e);
            }
        }
    }

    /**
     * Call the onLogin() method of all registered AuthHook(s)
     *
     * @param User $user
     */
    public static function triggerLogin(User $user)
    {
        /** @var AuthenticationHook $hook */
        foreach (Hook::all(self::NAME) as $hook) {
            try {
                $hook->onLogin($user);
            } catch (\Exception $e) {
                // Avoid error propagation if login failed in third party application
                Logger::error($e);
            }
        }
    }

    /**
     * Call the onLogout() method of all registered AuthHook(s)
     *
     * @param User $user
     */
    public static function triggerLogout(User $user)
    {
        /** @var AuthenticationHook $hook */
        foreach (Hook::all(self::NAME) as $hook) {
            try {
                $hook->onLogout($user);
            } catch (\Exception $e) {
                // Avoid error propagation if login failed in third party application
                Logger::error($e);
            }
        }
    }
}
