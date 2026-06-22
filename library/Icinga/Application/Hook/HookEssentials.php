<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;

/**
 * Provides the mechanism for hook registration and retrieval
 */
trait HookEssentials
{
    /**
     * Get the name that identifies this hook type
     *
     * @return string
     */
    abstract protected static function getHookName(): string;

    /**
     * Get whether the hook is registered
     *
     * @return bool
     */
    public static function isRegistered(): bool
    {
        return Hook::has(static::getHookName());
    }

    /**
     * Get all instances of the hook
     *
     * @return static[]
     */
    public static function all(): array
    {
        return Hook::all(static::getHookName());
    }

    /**
     * Get the first hook if any
     *
     * @return ?static
     */
    public static function first(): ?static
    {
        return Hook::first(static::getHookName());
    }

    /**
     * Register the hook
     *
     * @param ?bool $alwaysRun Whether to always run the hook without permission check.
     *   Defaults to {@see isAlwaysRun()}.
     */
    public static function register(?bool $alwaysRun = null): void
    {
        Hook::register(
            static::getHookName(),
            static::class,
            static::class,
            $alwaysRun ?? static::isAlwaysRun()
        );
    }

    /**
     * Get whether the hook always runs without a permission check
     *
     * Override this in a hook base class to change the default.
     */
    protected static function isAlwaysRun(): bool
    {
        return false;
    }
}
