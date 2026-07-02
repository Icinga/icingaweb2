<?php

// SPDX-FileCopyrightText: 2023 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Util;

use Exception;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Security\Csp\AttributedCsp;
use Icinga\Security\Csp\Loader\DashboardCspLoader;
use Icinga\Security\Csp\Loader\ModuleCspLoader;
use Icinga\Security\Csp\Loader\NavigationCspLoader;
use Icinga\Security\Csp\Loader\ArrayCspLoader;
use Icinga\User;
use Icinga\Web\Response;
use Icinga\Web\Window;
use ipl\Stdlib\Str;
use ipl\Web\Common\Csp as CspInstance;
use RuntimeException;

/**
 * Helper to manage the content security policy (CSP)
 *
 * {@see static::addHeader()} adds a Content-Security-Policy header with a nonce
 * to still support dynamic CSS securely.
 * Note that {@see static::createNonce()} must be called first.
 * Use {@see static::getStyleNonce()} to access the nonce for dynamic CSS.
 *
 * A nonce is not created for dynamic JS,
 * and it is questionable whether this will ever be supported.
 */
class Csp
{
    /** @var string The session namespace for CSP */
    public const SESSION_NAMESPACE = 'csp';

    /** @var string The session key for the nonce for dynamic CSS */
    public const SESSION_STYLE_NONCE = 'style_nonce';

    /** @var ?CspInstance */
    protected static ?CspInstance $csp = null;

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
     * @return void
     *
     * @throws RuntimeException If no nonce set for CSS
     */
    public static function addHeader(Response $response): void
    {
        $header = static::getHeader();
        if (! Str::isEmpty($header)) {
            $response->setHeader('Content-Security-Policy', $header, true);
        }
    }

    /**
     * Whether sending the CSP header is enabled
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return (bool) Config::app()->get('security', 'use_strict_csp', '0');
    }

    /**
     * Whether a custom, user-defined CSP header should be used
     *
     * @return bool
     */
    public static function isCustomEnabled(): bool
    {
        return (bool) Config::app()->get('security', 'use_custom_csp', '0');
    }

    /**
     * Whether the CSP header should be automatically generated
     *
     * This is currently always the opposite of {@see static::isCustomEnabled()}
     * as the CSP header is only generated if the custom CSP is not used. But this
     * might change in the future.
     *
     * @return bool
     */
    public static function isAutogenerationEnabled(): bool
    {
        return ! static::isCustomEnabled();
    }

    /**
     * Whether the CSP header should be generated for dashboards
     *
     * @return bool
     */
    public static function isDashboardEnabled(): bool
    {
        if (! static::isAutogenerationEnabled()) {
            return false;
        }

        return (bool) Config::app()->get('security', 'csp_enable_dashboards', '0');
    }

    /**
     * Whether the CSP header should be generated for modules. See {@see CspHook}
     *
     * @return bool
     */
    public static function isModuleEnabled(): bool
    {
        if (! static::isAutogenerationEnabled()) {
            return false;
        }

        return (bool) Config::app()->get('security', 'csp_enable_modules', '0');
    }

    /**
     * Whether the CSP header should be generated for the navigation
     *
     * @return bool
     */
    public static function isNavigationEnabled(): bool
    {
        if (! static::isAutogenerationEnabled()) {
            return false;
        }

        return (bool) Config::app()->get('security', 'csp_enable_navigation', '0');
    }

    /**
     * Get the default CSP for icingaweb
     *
     * @return AttributedCsp
     */
    public static function getSystemCsp(): AttributedCsp
    {
        $nonce = static::getStyleNonce();
        if ($nonce === null) {
            throw new RuntimeException('No nonce set for CSS');
        }

        return (new ArrayCspLoader('system', [
            'default-src' => ["'self'"],
            'style-src'   => ["'self'", "'nonce-$nonce'"],
            'font-src'    => ["'self'", 'data:'],
            'img-src'     => ["'self'", 'data:'],
            'frame-src'   => ["'self'"],
        ]))->loadForAllUsers()[0];
    }

    /**
     * Get the Content-Security-Policy header
     *
     * @return string The CSP header for this request
     */
    public static function getHeader(): string
    {
        if (static::$csp !== null) {
            return static::$csp->getHeader();
        }

        if (static::isCustomEnabled()) {
            try {
                static::$csp = self::getCustomHeader();
            } catch (Exception $e) {
                Logger::warning('Parsing custom CSP header failed: %s, falling back to system CSP', $e->getMessage());
                static::$csp = self::getSystemCsp()->csp;
            }
        } else {
            $auth = Auth::getInstance();
            $user = $auth->getUser();
            static::$csp = $user === null
                ? self::getSystemCsp()->csp
                : self::getAutomaticHeader($user);
        }

        return static::$csp->getHeader();
    }

    /**
     * Get the custom Content-Security-Policy set in the config
     *
     * This method automatically replaces new-lines and the {style_nonce} placeholder with the generated nonce.
     *
     * @return CspInstance The custom CSP header.
     */
    protected static function getCustomHeader(): CspInstance
    {
        $nonce = static::getStyleNonce();
        if (empty($nonce)) {
            throw new RuntimeException('No nonce set for CSS');
        }

        $config = Config::app();
        $customCsp = $config->get('security', 'custom_csp', '');
        $customCsp = str_replace('{style_nonce}', "'nonce-$nonce'", $customCsp);

        return CspInstance::fromString($customCsp);
    }

    /**
     * Get the automatically generated Content-Security-Policy
     *
     * @param User $user The user to generate the CSP for
     *
     * @return CspInstance The generated header value.
     */
    protected static function getAutomaticHeader(User $user): CspInstance
    {
        $attributedCsps = [static::getSystemCsp()];

        try {
            if (Csp::isModuleEnabled()) {
                $attributedCsps = array_merge($attributedCsps, (new ModuleCspLoader())->loadForUser($user));
            }
        } catch (Exception $e) {
            Logger::warning('Module CSP loader failed: %s', $e->getMessage());
        }

        try {
            if (Csp::isDashboardEnabled()) {
                $attributedCsps = array_merge($attributedCsps, (new DashboardCspLoader())->loadForUser($user));
            }
        } catch (Exception $e) {
            Logger::warning('Dashboard CSP loader failed: %s', $e->getMessage());
        }

        try {
            if (Csp::isNavigationEnabled()) {
                $attributedCsps = array_merge($attributedCsps, (new NavigationCspLoader())->loadForUser($user));
            }
        } catch (Exception $e) {
            Logger::warning('Navigation CSP loader failed: %s', $e->getMessage());
        }

        $csps = array_map(fn (AttributedCsp $csp) => $csp->csp, $attributedCsps);
        $result = new CspInstance();

        return $result->merge(...$csps);
    }

    /**
     * Ensure a nonce for dynamic CSS exists for the current window
     *
     * Does nothing if a nonce has already been created for this window's session,
     * otherwise generates one and persists it in thet session. Should be called
     * on every page load or reload, before {@see static::addHeader()} or
     * {@see static::getStyleNonce()} are used.
     *
     * @return void
     */
    public static function createNonce(): void
    {
        if (Window::getInstance()
                ->getSessionNamespace(static::SESSION_NAMESPACE)
                ->get(static::SESSION_STYLE_NONCE) === null
        ) {
            $nonce = base64_encode(random_bytes(16));
            Window::getInstance()
                ->getSessionNamespace(static::SESSION_NAMESPACE)
                ->set(static::SESSION_STYLE_NONCE, $nonce);
        }
    }

    /**
     * Get nonce for dynamic CSS
     *
     * @return ?string
     */
    public static function getStyleNonce(): ?string
    {
        if (Icinga::app()->isWeb() && static::$csp !== null) {
            return static::$csp->getNonce();
        }

        return Window::getInstance()->getSessionNamespace(static::SESSION_NAMESPACE)->get(static::SESSION_STYLE_NONCE);
    }
}
