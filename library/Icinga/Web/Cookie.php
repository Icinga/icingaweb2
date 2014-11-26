<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

/**
 * Helper Class Cookie
 *
 * @package Icinga\Web
 */
class Cookie
{
    /**
     * The name of the control cookie
     */
    const CHECK_COOKIE = '_chc';

    /**
     * Check whether cookies are supported or not
     *
     * @return bool
     */
    public static function isSupported()
    {
        if (! empty($_COOKIE)) {
            self::cleanupCheck();
            return true;
        }

        if (isset($_REQUEST['_checkCookie']) && empty($_COOKIE)) {
            return false;
        }

        if (! isset($_REQUEST['_checkCookie'])) {
            self::provideCheck();
        }

        return false;
    }

    /**
     * Redirect to a given uri
     *
     * @param string $uri
     */
    public static function redirect($uri)
    {
        header('location: ' . $uri);
        exit(0);
    }

    /**
     * Prepare check to detect cookie support
     */
    public static function provideCheck()
    {
        setcookie(self::CHECK_COOKIE, '1');

        if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY)) {
            $requestUri = $_SERVER['REQUEST_URI'] . '&_checkCookie=1';
        } else {
            $requestUri = $_SERVER['REQUEST_URI'] . '?_checkCookie=1';
        }

        self::redirect($requestUri);
    }

    /**
     * Cleanup the cookie support check
     */
    public static function cleanupCheck()
    {
        if (isset($_REQUEST['_checkCookie']) && isset($_COOKIE[self::CHECK_COOKIE])) {
            $requestUri = preg_replace('/([&|\?]_checkCookie=1)/', '', $_SERVER['REQUEST_URI']);
            self::redirect($requestUri);
        }
    }
}
