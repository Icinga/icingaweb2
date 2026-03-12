<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Util;

use Icinga\Application\Hook\CspDirectiveHook;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Data\ConfigObject;
use Icinga\Web\Response;
use Icinga\Web\Window;
use Icinga\Application\Config;
use RuntimeException;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Widget\Dashboard;

use Throwable;
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
    /** @var self|null */
    protected static ?self $instance = null;

    /** @var ?string */
    protected ?string $styleNonce = null;

    /** Singleton */
    private function __construct()
    {
    }

    /**
     * Add a Content-Security-Policy header with a nonce for dynamic CSS
     *
     * Note that {@see static::createNonce()} must be called beforehand.
     *
     * @param Response $response
     *
     * @throws RuntimeException If no nonce set for CSS
     */
    public static function addHeader(Response $response): void
    {
        $header = static::getContentSecurityPolicy();
        $response->setHeader('Content-Security-Policy', $header, true);
    }

    public static function isCspEnabled(): bool
    {
        return Config::app()->get('security', 'use_strict_csp', 'n') === 'y';
    }

    public static function collectContentSecurityPolicyDirectives(): array
    {
        $policyDirectives = [];

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

            if ($host === null) {
                continue;
            }

            $policy = $host;
            if ($scheme !== null) {
                $policy = "$scheme://$host";
            }

            $policyDirectives[] = [
                'directives' => [
                    'frame-src' => [$policy],
                ],
                'reason'   => $navigationItem['reason'],
            ];
        }

        // Allow modules to add their own csp directives in a limited fashion.
        foreach (CspDirectiveHook::all() as $hook) {
            $directives = [];
            try {
                foreach ($hook->getCspDirectives() as $directive => $policies) {
                    // policy names contain only lowercase letters and '-'. Reject anything else.
                    if (! preg_match('|^[a-z\-]+$|', $directive)) {
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

                    if (count($policies) === 0) {
                        continue;
                    }

                    $directives[$directive] = $policies;
                }

                if (count($directives) === 0) {
                    continue;
                }

                $policyDirectives[] = [
                    "directives" => $directives,
                    "reason"     => [
                        "type" => "hook",
                        "hook" => get_class($hook),
                    ],
                ];
            } catch (Throwable $e) {
                Logger::error('Failed to CSP hook on request: %s', $e);
            }
        }

        return $policyDirectives;
    }

    /**
     * Get the Content-Security-Policy.
     *
     * @throws RuntimeException If no nonce set for CSS
     *
     * @return string Returns the generated header value.
     */
    public static function getContentSecurityPolicy(): string
    {
        $config = Config::app();
        if ($config->get('security', 'use_custom_csp', 'y') === 'y') {
            return self::getCustomContentSecurityPolicy();
        }

        return self::getAutomaticContentSecurityPolicy();
    }

    public static function getCustomContentSecurityPolicy(): ?string
    {
        $csp = static::getInstance();

        if (empty($csp->styleNonce)) {
            throw new RuntimeException('No nonce set for CSS');
        }

        $config = Config::app();
        $raw = $config->get('security', 'custom_csp');
        $formated = str_replace('{style_nonce}', "'nonce{$csp->styleNonce}'", $raw);
        return $formated;
    }

    /**
     * Get the automatically generated Content-Security-Policy.
     *
     * @throws RuntimeException If no nonce set for CSS
     *
     * @return string Returns the generated header value.
     */
    public static function getAutomaticContentSecurityPolicy(): string
    {
        $csp = static::getInstance();

        if (empty($csp->styleNonce)) {
            throw new RuntimeException('No nonce set for CSS');
        }

        // These are the default directives that should always be enforced. 'self' is valid for all
        // directives and will therefore not be listed here.
        $cspDirectives = [
            'style-src' => ["'nonce-{$csp->styleNonce}'"],
            'font-src' => ["data:"],
            'img-src' => ["data:"],
            'frame-src' => []
        ];

        $policyDirectives = self::collectContentSecurityPolicyDirectives();

        foreach ($policyDirectives as $directive) {
            foreach ($directive['directives'] as $directive => $policies) {
                if (! isset($cspDirectives[$directive])) {
                    $cspDirectives[$directive] = [];
                }
                $cspDirectives[$directive] = array_merge($cspDirectives[$directive], $policies);
            }
        }

        $header = "default-src 'self'; ";
        foreach ($cspDirectives as $directive => $policyDirectives) {
            if (!empty($policyDirectives)) {
                $header .= ' ' . implode(' ', array_merge([$directive, "'self'"], array_unique($policyDirectives))) . ';';
            }
        }
        
        return $header;
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
    protected static function fetchDashletNavigationItemConfigs(): array
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
    protected static function fetchNavigationItems(): array
    {
        $user = Auth::getInstance()->getUser();
        $menuItems = [];
        if ($user === null) {
            return $menuItems;
        }
        $navigationType = Navigation::getItemTypeConfiguration();
        foreach ($navigationType as $type => $_) {
            $config = Config::navigation($type, $user->getUsername());
            $config->getConfigObject()->setKeyColumn('name');
            foreach ($config->select() as $itemConfig) {
                if ($itemConfig->get("target", "") !== "_blank") {
                    $menuItems[] = [
                        "name" => $itemConfig->get('name'),
                        "url" => $itemConfig->get('url'),
                        "reason" => [
                            'type' => 'navigation',
                            'name' => $itemConfig->get('name'),
                            'shared' => false,
                        ]
                    ];
                }
            }
            $configShared = Config::navigation($type);
            $configShared->getConfigObject()->setKeyColumn('name');
            foreach ($configShared->select() as $itemConfig) {
                if (
                    Icinga::app()->hasAccessToSharedNavigationItem($itemConfig, $config) &&
                    $itemConfig->get("target", "") !== "_blank"
                ) {
                    $menuItems[] = [
                        "name" => $itemConfig->get('name'),
                        "url" => $itemConfig->get('url'),
                        "reason" => [
                            'type' => 'navigation',
                            'name' => $itemConfig->get('name'),
                            'shared' => true,
                        ]
                    ];
                }
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
    protected static function fetchDashletsItems(): array
    {
       $user = Auth::getInstance()->getUser();
       $dashlets = [];
       if ($user === null) {
          return $dashlets;
       }

       $dashboard = new Dashboard();
       $dashboard->setUser($user);
       $dashboard->load();

       foreach ($dashboard->getPanes() as $pane) {
          foreach ($pane->getDashlets() as $dashlet) {
             $url = $dashlet->getUrl();
             if ($url === null) {
                continue;
             }

             $externalUrl = $url->getParam("url");
             if ($externalUrl !== null && filter_var($externalUrl, FILTER_VALIDATE_URL) !== false) {
                $dashlets[] = [
                    "name" => $dashlet->getName(),
                    "url" => $externalUrl,
                    "reason" => [
                        "type" => "dashlet",
                        "user" => $user->getUsername(),
                        "pane" => $pane->getName(),
                        "dashlet" => $dashlet->getName(),
                    ],
                ];
                continue;
             }

             if ($url->isExternal()) {
                $absoluteUrl = $url->getAbsoluteUrl();
                if (filter_var($absoluteUrl, FILTER_VALIDATE_URL) !== false) {
                    $dashlets[] = [
                       "name" => $dashlet->getName(),
                       "url" => $absoluteUrl,
                       "reason" => [
                            "type" => "dashlet-iframe",
                            "user" => $user->getUsername(),
                            "pane" => $pane->getName(),
                            "dashlet" => $dashlet->getName(),
                        ],
                    ];
                }
             }
          }
       }
       return $dashlets;
    }

}
