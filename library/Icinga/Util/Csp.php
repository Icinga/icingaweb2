<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Util;

use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Security\SecurityException;
use Icinga\Web\Response;
use Icinga\Web\Window;
use ipl\I18n\StaticTranslator;
use RuntimeException;

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
        $csp = static::getInstance();

        if (empty($csp->styleNonce)) {
            throw new RuntimeException('No nonce set for CSS');
        }

        $header = "default-src 'self'; style-src 'self' 'nonce-{$csp->styleNonce}'; ";
        $imageSourceWhitelist = Icinga::app()->getConfig()->get("security", "image_source_whitelist", "");
        $header = $header . Csp::getImageSourceDirective($imageSourceWhitelist);

        $response->setHeader(
            'Content-Security-Policy',
            $header,
            true
        );
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
        $csp->styleNonce = base64_encode(random_bytes(16));

        Window::getInstance()->getSessionNamespace('csp')->set('style_nonce', $csp->styleNonce);
    }

    /**
     * Get nonce for dynamic CSS
     *
     * @return ?string
     */
    public static function getStyleNonce(): ?string
    {
        if (Icinga::app()->isCli()) {
            return null;
        }
        return static::getInstance()->styleNonce;
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

    public static function getImageSourceDirective(string $whitelist): string {
        $directives = ["img-src", "'self'", "data:"];
        foreach (explode(",", $whitelist) as $domain) {
            try {
                $directives[] = Csp::validateImageSourceWhitelistItem($domain);
            } catch (SecurityException $e) {
                Logger::error("Ignoring domain '$domain' as it is not valid. $e");
            }
        }

        return implode(' ', $directives) . ";";
    }

    /**
     * Validates and trims an item that is used as a domain in the img-src directive.
     * Will throw an error, if the user tries to whitelist everything (*) or tries
     * to inject special characters that might allow to escape and edit the whole csp.
     *
     * @throws SecurityException
     */
    public static function validateImageSourceWhitelistItem(string $item): string {
        $item = trim($item);
        // Don't allow general whitelisting of all domains
        if ($item == '*') {
            throw new SecurityException(
                StaticTranslator::$instance->translate("Whitelisting all domains is not allowed.")
            );
        }

        // Don't allow special characters that might allow to escape and edit the whole csp.
        // Otherwise, e.g. "example.com; script-src *" will allow the user to change other directives.
        $csp_config_regex = "|^\*?[a-zA-Z0-9+._\-:]*$|";
        if (!preg_match($csp_config_regex, $item)) {
            throw new SecurityException(
                StaticTranslator::$instance->translate("The following domain is invalid: ")
                . $item
            );
        }

        return $item;
    }
}
