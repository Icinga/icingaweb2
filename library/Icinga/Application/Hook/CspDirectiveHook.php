<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;

abstract class CspDirectiveHook
{
    /**
     * Allow the module to provide custom directives for the CSP header. The return value should be an array
     * with directive as the key and the policies in an array as the value. The valid values can either be
     * a concrete host, whitelisting subdomains for hosts or a custom nonce for that module.
     *
     * Example: [ 'img-src' => [ 'https://*.media.tumblr.com', 'https://http.cat/' ] ]
     *
     * @return array<string, string[]> The CSP directives are the keys and the policies the values.
     */
    abstract public function getCspDirectives(): array;

    /**
     * Get all registered implementations
     *
     * @return static[]
     */
    public static function all(): array
    {
        return Hook::all('CspDirective');
    }

    /**
     * Register the class as a RequestHook implementation
     *
     * Call this method on your implementation during module initialization to make Icinga Web aware of your hook.
     */
    public static function register(): void
    {
        Hook::register('CspDirective', static::class, static::class, true);
    }
}
