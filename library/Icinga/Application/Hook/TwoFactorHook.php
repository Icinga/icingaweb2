<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;
use Icinga\Authentication\TwoFactor;

abstract class TwoFactorHook implements TwoFactor
{
    public const NAME = 'TwoFactor';

    /**
     * Register the class as a two-factor hook implementation
     *
     * Call this on your implementation during module initialization to make Icinga Web aware of your hook.
     */
    public static function register(): void
    {
        Hook::register(static::NAME, static::class, static::class, true);
    }

    /**
     * Get all registered implementations sorted alphabetically by their name
     *
     * @return TwoFactor[]
     */
    public static function all(): array
    {
        $twoFactorMethods = [];
        foreach (Hook::all(static::NAME) as $method) {
            $twoFactorMethods[$method->getName()] = $method;
        }
        ksort($twoFactorMethods, SORT_STRING | SORT_FLAG_CASE);

        return array_values($twoFactorMethods);
    }

    /**
     * Get the alphabetically first implementation, or null if none are registered
     *
     * @return ?TwoFactor
     */
    public static function first(): ?TwoFactor
    {
        return static::all()[0] ?? null;
    }

    /**
     * Get the implementation with the given name, or null if no such implementation is registered
     *
     * @param string $name The value returned by {@link TwoFactor::getName()} of the desired implementation
     *
     * @return ?TwoFactor
     */
    public static function fromName(string $name): ?TwoFactor
    {
        foreach (static::all() as $method) {
            if ($method->getName() === $name) {
                return $method;
            }
        }

        return null;
    }

    /**
     * Get the implementation the currently authenticated user is enrolled in, or null if they are not enrolled
     *
     * @return ?TwoFactor
     */
    public static function loadEnrolled(): ?TwoFactor
    {
        foreach (static::all() as $method) {
            if ($method->isEnrolled()) {
                return $method;
            }
        }

        return null;
    }
}
