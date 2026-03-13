<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Util;

use Generator;
use Icinga\Application\Config;
use Icinga\Application\Hook\CspDirectiveHook;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Data\ConfigObject;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\Response;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Window;
use RuntimeException;
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
        $policyDirectives = self::fetchDashletNavigationItemConfigs();

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
     * @return string Returns the generated header value.
     * @throws RuntimeException If no nonce set for CSS
     *
     */
    public static function getContentSecurityPolicy(): string
    {
        $config = Config::app();
        if ($config->get('security', 'use_custom_csp', 'y') === 'y') {
            return self::getCustomContentSecurityPolicy();
        }

        return self::getAutomaticContentSecurityPolicy();
    }

    protected static function getCustomContentSecurityPolicy(): ?string
    {
        $csp = static::getInstance();

        if (empty($csp->styleNonce)) {
            throw new RuntimeException('No nonce set for CSS');
        }

        $config = Config::app();
        $customCsp = $config->get('security', 'custom_csp');
        $customCsp = str_replace("\r\n", ' ', $customCsp);
        $customCsp = str_replace("\n", ' ', $customCsp);
        $customCsp = str_replace('{style_nonce}', "'nonce-{$csp->styleNonce}'", $customCsp);
        return $customCsp;
    }

    /**
     * Get the automatically generated Content-Security-Policy.
     *
     * @return string Returns the generated header value.
     * @throws RuntimeException If no nonce set for CSS
     *
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
            'font-src'  => ["data:"],
            'img-src'   => ["data:"],
            'frame-src' => [],
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
            if (! empty($policyDirectives)) {
                $header .= ' ' .
                    implode(' ', array_merge([$directive, "'self'"], array_unique($policyDirectives)))
                    . ';';
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
                        get_php_type($nonce),
                    ),
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
     */
    protected static function fetchDashletNavigationItemConfigs(): array
    {
        return array_merge(
            self::fetchNavigationItems(),
            self::fetchDashletsItems(),
        );
    }

    /**
     * Fetches navigation items for the current user.
     *
     * Iterates through all registered navigation types, loads both user-specific
     * and shared configurations, and returns a list of menu items.
     *
     * @return array A list of CSP directives, one for each navigation-item that has an external URL.
     */
    protected static function fetchNavigationItems(): array
    {
        $auth = Auth::getInstance();
        if (! $auth->isAuthenticated()) {
            return [];
        }

        $origins = [];
        $navigationType = Navigation::getItemTypeConfiguration();
        foreach ($navigationType as $type => $_) {
            $navigation = new Navigation();
            foreach ($navigation->load($type) as $navItem) {
                foreach (self::yieldNavigation($navItem) as $name => $url) {
                    $origins[] = [
                        'directives' => [
                            'frame-src' => [$url->getScheme() . '://' . $url->getHost()],
                        ],
                        'reason' => [
                            'type'   => 'navigation',
                            'name'   => $name,
                            'parent' => $navItem->getName(),
                            'navType' => $type,
                        ]
                    ];
                }
            }
        }

        return $origins;
    }

    protected static function yieldNavigation(NavigationItem $item): Generator
    {
        if ($item->hasChildren()) {
            foreach ($item as $child) {
                yield from self::yieldNavigation($child);
            }
        } else {
            $url = $item->getUrl();
            if ($url === null) {
                return;
            }
            if ($item->getTarget() !== '_blank' && $url->isExternal()) {
                yield $item->getName() => $item->getUrl();
            }
        }
    }

    /**
     * Fetches all dashlets for the current user that have an external URL.
     *
     * @return array A list of CSP directives, one for each dashlet that has an external URL.
     */
    protected static function fetchDashletsItems(): array
    {
        $user = Auth::getInstance()->getUser();
        $origins = [];
        if ($user === null) {
            return $origins;
        }

        $dashboard = new Dashboard();
        $dashboard->setUser($user);
        $dashboard->load();

        /** @var Dashboard\Pane $pane */
        foreach ($dashboard->getPanes() as $pane) {
            /** @var Dashboard\Dashlet $dashlet */
            foreach ($pane->getDashlets() as $dashlet) {
                $url = $dashlet->getUrl();
                if ($url === null) {
                    continue;
                }

                $absoluteUrl = $url->isExternal()
                    ? $url->getAbsoluteUrl()
                    : $url->getParam('url');
                if ($absoluteUrl === null || filter_var($absoluteUrl, FILTER_VALIDATE_URL) === false) {
                    continue;
                }

                $origins[] = [
                    'directives' => [
                        'frame-src' => [$absoluteUrl],
                    ],
                    'reason' => [
                        'type'    => 'dashlet',
                        'user'    => $user->getUsername(),
                        'pane'    => $pane->getName(),
                        'dashlet' => $dashlet->getName(),
                    ]
                ];
            }
        }
        return $origins;
    }
}
