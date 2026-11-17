<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Icinga\User;
use Icinga\Application\Logger;
use Icinga\Web\Url;
use Throwable;

/**
 * Icinga Web Authentication Hook base class
 *
 * This hook can be used to authenticate the user in a third party application.
 * Extend this class if you want to perform arbitrary actions during the login and logout.
 */
abstract class AuthenticationHook
{
    use HookEssentials;

    /**
     * Name of the hook
     */
    const NAME = 'authentication';

    final protected static function getHookName(): string
    {
        return static::NAME;
    }

    /**
     * Triggered after login in Icinga Web and when calling login action
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
     * Get the URL to redirect the browser to after logout from Icinga Web
     *
     * Icinga Web asks for this URL after {@see onLogout()} and before it destroys the session,
     * so the hook can still read its session data. Icinga Web then destroys the session
     * and redirects the browser to the returned URL instead of the login page.
     * The URL may point to an external site, such as the end-session endpoint of an identity provider.
     *
     * Icinga Web doesn't ask for this URL when an externally authenticated user logs out.
     * Such a logout must answer with status 401 so that the browser forgets the credentials.
     *
     * If more than one hook returns a URL, the first one wins.
     * Icinga Web ignores the others and logs a warning for each.
     *
     * @param User $user The user who logs out
     *
     * @return ?Url The URL to redirect to, or null to redirect to the login page
     */
    public function getLogoutRedirectUrl(User $user): ?Url
    {
        return null;
    }

    /**
     * Call the onAuthFromSession() method of all registered {@link AuthenticationHook}s
     *
     * @param User $user
     */
    public static function triggerAuthFromSession(User $user): void
    {
        foreach (static::all() as $hook) {
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
        foreach (static::all() as $hook) {
            try {
                $hook->onLogin($user);
            } catch (Throwable $e) {
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
        foreach (static::all() as $hook) {
            try {
                $hook->onLogout($user);
            } catch (Throwable $e) {
                // Avoid error propagation if login failed in third party application
                Logger::error($e);
            }
        }
    }

    /**
     * Get the URL to redirect the browser to after logout from all registered {@link AuthenticationHook}s
     *
     * Call this before destroying the session. Errors of a hook are logged and the hook is skipped.
     *
     * @param User $user The user who logs out
     *
     * @return ?Url The URL of the first hook that returns one, or null if none does
     */
    public static function getLogoutRedirectUrlFromHooks(User $user): ?Url
    {
        $redirectUrl = null;
        $redirectHook = null;

        foreach (static::all() as $hook) {
            try {
                $url = $hook->getLogoutRedirectUrl($user);
            } catch (Throwable $e) {
                // Avoid error propagation if a hook failed in a third party application.
                Logger::error($e);

                continue;
            }

            if ($url === null) {
                continue;
            }

            if ($redirectUrl === null) {
                $redirectUrl = $url;
                $redirectHook = $hook;
            } else {
                Logger::warning(
                    'Ignored logout redirect URL of authentication hook %s because %s already provided one',
                    $hook::class,
                    $redirectHook::class
                );
            }
        }

        return $redirectUrl;
    }
}
