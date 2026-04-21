<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;
use Icinga\Authentication\TwoFactor;

abstract class TwoFactorHook implements TwoFactor
{
    /**
     * Load the implementation from the method name
     *
     * @param string $name
     *
     * @return ?TwoFactor
     */
    public static function fromName(string $name): ?TwoFactor
    {
        foreach (static::all() as $twoFactorMethod) {
            if ($twoFactorMethod->getName() === $name) {
                return $twoFactorMethod;
            }
        }

        return null;
    }

    /**
     * Load two-factor method configured in the database
     *
     * @return ?TwoFactor Return the configured two-factor method or null if no method is configured
     */
    public static function loadFromDb(): ?TwoFactor
    {
        // TODO (jr): get method from database
        $dbMethod = '...';

        return static::fromName($dbMethod);
    }

    /**
     * Get all registered implementations sorted by the method names
     *
     * @return array<string, static>
     */
    public static function all(): array
    {
        $twoFactorMethods = [];
        foreach (Hook::all('TwoFactor') as $method) {
            $twoFactorMethods[$method->getName()] = $method;
        }
        ksort($twoFactorMethods, SORT_STRING | SORT_FLAG_CASE);

        return array_values($twoFactorMethods);
    }

    /**
     * Register the class as a two-factor hook implementation
     *
     * Call this method on your implementation during module initialization to make Icinga Web aware of your hook.
     */
    public static function register(): void
    {
        Hook::register('TwoFactor', static::class, static::class, true);
    }
}
