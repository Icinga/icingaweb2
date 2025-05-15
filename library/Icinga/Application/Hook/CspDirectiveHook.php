<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

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
}
