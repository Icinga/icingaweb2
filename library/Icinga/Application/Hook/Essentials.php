<?php
/* Icinga Web 2 | (c) 2026 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook;

use Icinga\Application\Hook;

/**
 * All hooks are provided and consumed - this trait provides the mechanism
 */
trait Essentials
{
    /**
     * Get the name for {@link Hook::register} and {@link Hook::all}
     */
    abstract protected static function getHookName(): string;

    /**
     * Whether someone registered itself for the hook
     *
     * @return bool
     */
    public static function registered(): bool
    {
        return Hook::has(static::getHookName());
    }

    /**
     * Return all instances of the hook
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
     * Register a hook provider
     *
     * @param bool $alwaysRun Whether to always run the hook, without permission check
     */
    public static function register(bool $alwaysRun = false): void
    {
        Hook::register(static::getHookName(), static::class, static::class, $alwaysRun);
    }
}
