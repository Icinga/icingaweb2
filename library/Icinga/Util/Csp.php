<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Util;

use Icinga\Application\Hook;
use Icinga\Application\Hook\CspDirectiveHook;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Data\ConfigObject;
use Icinga\User;
use Icinga\Web\Response;
use Icinga\Web\Window;
use Icinga\Application\Config;
use RuntimeException;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Widget\Dashboard;

use function ipl\Stdlib\get_php_type;

/**
 * Helper to enable strict content security policy (CSP)
 *
 * {@see static::addHeader()} adds a strict Content-Security-Policy header with a nonce to still support dynamic CSS
 * securely.
 * Note that {@see static::createNonce()} must be called first.
 * Use {@see static::getStyleNonce()} to access the nonce for dynamic CSS.
 *
 * A nonce is not created for dynamic JS,
 * and it is questionable whether this will ever be supported.
 */
class Csp
{
    /** @var static */
    protected static $instance;

    /** @var ?string */
    protected $styleNonce;

    /** Singleton */
    private function __construct()
    {
    }

    /**
     * Add Content-Security-Policy header with a nonce for dynamic CSS
     *
     * Note that {@see static::createNonce()} must be called beforehand.
     *
     * @param Response $response
     *
     * @throws RuntimeException If no nonce set for CSS
     */
    public static function addHeader(Response $response): void
    {
        $user = Auth::getInstance()->getUser();
        $header = static::getContentSecurityPolicy();
        Logger::debug("Setting Content-Security-Policy header for user {$user->getUsername()} to $header");
        $response->setHeader('Content-Security-Policy', $header, true);
    }

    /**
     * Get the Content-Security-Policy for a specific user.
     *
     * @param User $user
     *
     * @throws RuntimeException If no nonce set for CSS
     *
     * @return string Returns the generated header value.
     */
    public static function getContentSecurityPolicy(): string
    {
        $csp = static::getInstance();

        if (empty($csp->styleNonce)) {
            throw new RuntimeException('No nonce set for CSS');
        }

        // These are the default directives that should always be enforced. 'self' is valid for all
        // directives and will therefor not be listed here.
        $cspDirectives = [
            'style-src' => ["'nonce-{$csp->styleNonce}'"],
            'font-src' => ["data:"],
            'img-src' => ["data:"],
            'frame-src' => []
        ];

        // Whitelist the hosts in the custom NavigationItems configured for the user,
        // so that the iframes can be rendered properly.
        /** @var ConfigObject[] $navigationItems */
        $navigationItems = self::fetchDashletNavigationItemConfigs();
        foreach ($navigationItems as $navigationItem) {
            $errorSource = sprintf("Navigation item %s", $navigationItem['name']);

            $host = parse_url($navigationItem["url"], PHP_URL_HOST);
            // Make sure $url is actually valid;
            if (filter_var($navigationItem["url"], FILTER_VALIDATE_URL) === false) {
                Logger::debug("$errorSource: Skipping invalid url: $host");
                continue;
            }

            $scheme = parse_url($navigationItem["url"], PHP_URL_SCHEME);


            if ($host === null || !static::validateCspPolicy($errorSource, "frame-src", $host)) {
                continue;
            }

            $policy = $host;
            if ($scheme !== null) {
                $policy = "$scheme://$host";
            }

            $cspDirectives['frame-src'][] = $policy;
        }
        // Allow modules to add their own csp directives in a limited fashion.
        /** @var CspDirectiveHook $hook */
        foreach (Hook::all('CspDirective') as $hook) {
            foreach ($hook->getCspDirectives() as $directive => $policies) {
                // policy names contain only lowercase letters and '-'. Reject anything else.
                if (!preg_match('|^[a-z\-]+$|', $directive)) {
                    $errorSource = get_class($hook);
                    Logger::debug("$errorSource: Invalid CSP directive found: $directive");
                    continue;
                }

                // The default-src can only ever be 'self'. Disallow any updates to it.
                if ($directive === "default-src") {
                    $errorSource = get_class($hook);
                    Logger::debug("$errorSource: Changing default-src is forbidden.");
                    continue;
                }

                $cspDirectives[$directive] = $cspDirectives[$directive] ?? [];
                foreach ($policies as $policy) {
                    $source = get_class($hook);
                    if (!static::validateCspPolicy($source, $directive, $policy)) {
                        continue;
                    }

                    $cspDirectives[$directive][] = $policy;
                }
            }
        }

        $header = "default-src 'self'; ";
        foreach ($cspDirectives as $directive => $policies) {
            if (!empty($policies)) {
                $header .= ' ' . implode(' ', array_merge([$directive, "'self'"], array_unique($policies))) . ';';
            }
        }
        
        return $header;
    }

    public static function validateCspPolicy(string $source, string $directive, string $policy): bool
    {
        // We accept the following policies:
        //     1. Hosts: Modules can whitelist certain domains as sources for the CSP header directives.
        //         - A host can have a specific scheme (http or https).
        //         - A host can whitelist all subdomains with *
        //         - A host can contain all alphanumeric characters as well as '+', '-', '_', '.', and ':'
        //     2. Nonce: Modules are allowed to specify custom nonce for some directives.
        //         - A nonce is enclosed in single-quotes: "'"
        //         - A nonce begins with 'nonce-' followed by at least 22 significant characters of base64 encoded data.
        //           as recommended by the standard: https://content-security-policy.com/nonce/
        if (! preg_match("/^((https?:\/\/)?\*?[a-zA-Z0-9+._\-:]+|'nonce-[a-zA-Z0-9+\/]{22,}={0,3}')$/", $policy)) {
            Logger::debug("$source: Invalid CSP policy found: $directive $policy");
            return false;
        }

        // We refuse all overly aggressive whitelisting by default. This includes:
        //     1. Whitelisting all Hosts with '*'
        //     2. Whitelisting all Hosts in a tld, e.g. 'http://*.com'
        if (preg_match('|\*(\.[a-zA-Z]+)?$|', $directive)) {
            Logger::debug("$source: Disallowing whitelisting all hosts. $directive");
            return false;
        }

        return true;
    }

    /**
     * Set/recreate nonce for dynamic CSS
     *
     * Should always be called upon initial page loads or page reloads,
     * as it sets/recreates a nonce for CSS and writes it to a window-aware session.
     */
    public static function createNonce(): void
    {
        $csp = static::getInstance();
        if ($csp->styleNonce === null) {
            $csp->styleNonce = base64_encode(random_bytes(16));
            Window::getInstance()->getSessionNamespace('csp')->set('style_nonce', $csp->styleNonce);
        }
    }

    /**
     * Get nonce for dynamic CSS
     *
     * @return ?string
     */
    public static function getStyleNonce(): ?string
    {
        if (Icinga::app()->isWeb()) {
            return static::getInstance()->styleNonce;
        }
        return null;
    }

    /**
     * Get the CSP instance
     *
     * @return self
     */
    protected static function getInstance(): self
    {
        if (static::$instance === null) {
            $csp = new static();
            $nonce = Window::getInstance()->getSessionNamespace('csp')->get('style_nonce');
            if ($nonce !== null && ! is_string($nonce)) {
                throw new RuntimeException(
                    sprintf(
                        'Nonce value is expected to be string, got %s instead',
                        get_php_type($nonce)
                    )
                );
            }

            $csp->styleNonce = $nonce;

            static::$instance = $csp;
        }

        return static::$instance;
    }
    

    /**
     * Fetches and merges configurations for navigation menu items and dashlets.
     *
     * @return array An array containing both navigation items and dashlet configurations.
     * // returns [['name' => 'Item Name', 'url' => 'https://example.com'], ...]
     */
    protected static function fetchDashletNavigationItemConfigs()
    {
        return array_merge(
            self::fetchNavigationItems(),
            self::fetchDashletsItems()
        );
    }

    /**
     * Fetches navigation items for the current user.
     *
     * Iterates through all registered navigation types, loads both user-specific
     * and shared configurations, and returns a list of menu items.
     *
     * @return array Each item is an associative array with 'name' and 'url' keys.
     * Example: [ ['name' => 'Home', 'url' => '/'], ['name' => 'Profile', 'url' => '/profile'] ]
     */
    protected static function fetchNavigationItems()
    {
        $username = Auth::getInstance()->getUser()->getUsername();
        $navigationType = Navigation::getItemTypeConfiguration();
        foreach ($navigationType as $type => $_) {
            $config = Config::navigation($type, $username);
            $config->getConfigObject()->setKeyColumn('name');
            $configShared = Config::navigation($type);
            $configShared->getConfigObject()->setKeyColumn('name');
            foreach ($config->select() as $itemConfig) {
                $menuItems[] = ["name" => $itemConfig->get('name'), "url" => $itemConfig->get('url')];
            }
            foreach ($configShared->select() as $itemConfig) {
                $menuItems[] = ["name" => $itemConfig->get('name'), "url" => $itemConfig->get('url')];
            }
        }
        return $menuItems;
    }

    /**
     * Fetches all dashlets for the current user that have an external URL.
     *
     * @return array A list of dashlets with their names and absolute URLs.
     * // returns [['name' => 'Dashlet Name', 'url' => 'https://external.dashlet.com'], ...]
     */
    protected static function fetchDashletsItems()
    {
        $dashboard = new Dashboard();
        $dashboard->setUser(Auth::getInstance()->getUser());
        $dashboard->load();
        $dashlets = [];
        foreach ($dashboard->getPanes() as $pane) {
            foreach ($pane->getDashlets() as $dashlet) {
                $url = $dashlet->getUrl();
                // Prefer explicit external URL parameter if present
                $externalUrl = $url->getParam("url");
                if ($externalUrl !== null) {
                    $dashlets[] = [
                        "name" => $dashlet->getName(),
                        "url" => $externalUrl
                    ];
                    continue;
                }

                // Otherwise, check if the dashlet URL itself is external
                if ($url->isExternal()) {
                    $dashlets[] = [
                        "name" => $dashlet->getName(),
                        "url" => $url->getAbsoluteUrl()
                    ];
                }
            }
        }

        return $dashlets;
    }

}
