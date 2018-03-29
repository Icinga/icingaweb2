<?php

namespace Icinga\Application\Hook;

use Icinga\User;
use Icinga\Web\Hook;
use Icinga\Application\Logger;
use Icinga\Web\Request;
use Icinga\Web\Url;

/**
 * Icinga Web Authentication Hook base class
 *
 * This hook can be used to authenticate the user in a third party application.
 * Extend this class if you want to perform arbitrary actions during the login and logout.
 */
abstract class AuthenticationHook
{
    /**
     * Name of the hook
     */
    const NAME = 'authentication';

    /**
     * Triggered after login in Icinga Web and when calling login action even if already authenticated in Icinga Web
     *
     * @param User $user
     */
    public function onLogin(User $user)
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
     * Return Url to which redirect the user after login. The implementation of this method must inspect the request and
     * if it is responsible for it must return the Url to which redirect the user otherwise it must return null to allow
     * others AuthenticationHook(s) to process the request.
     *
     * If no hooks return an Url the default redirect Url from Icinga Web will be used.
     *
     * @return Url|null
     */
    public function onLoginRedirect(Request $request)
    {
        return null;
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

    /**
     * Retrieve the Url from the onLoginRedirect() method of all registered AuthenticationHook(s).
     * If multiple AuthenticationHook(s) returns a Url the first one will be returned and a warning will be logged.
     * If nobody returns a Url, null will be returned.
     *
     * @param Request $request
     * @return Url|null
     */
    public static function retrieveOnLoginRedirect(Request $request)
    {
        /** @var array[string]Url $urls */
        $urls = array();

        /** @var AuthenticationHook $hook */
        foreach (Hook::all(self::NAME) as $hook) {
            try {
                $url = $hook->onLoginRedirect($request);
                if ($url instanceof Url) {
                    $urls[get_class($hook)] = $url;
                }
            } catch (\Exception $e) {
                // Avoid error propagation if login failed in third party application
                Logger::error($e);
            }
        }

        if (empty($urls)) {
            return null;
        }

        if (count($urls) > 1) {
            Logger::warning("Multiple AuthenticationHook(s) returned a redirect url: " . join(", ", array_keys($urls)));
        }

        // return the first Url in the array
        return $urls[array_keys($urls)[0]];
    }
}
