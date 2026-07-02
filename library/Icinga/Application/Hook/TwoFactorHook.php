<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Generator;
use Icinga\Application\Logger;
use Icinga\Authentication\TwoFactor;
use Icinga\Exception\IcingaException;
use Icinga\User;
use RuntimeException;
use Throwable;

/**
 * Abstract base class for two-factor authentication hook implementations
 */
abstract class TwoFactorHook implements TwoFactor
{
    use HookEssentials {
        all as private essentialsAll;
    }

    final protected static function getHookName(): string
    {
        return 'TwoFactor';
    }

    final protected static function isAlwaysRun(): bool
    {
        return true;
    }

    /**
     * Get all registered implementations sorted alphabetically by their display name
     *
     * @return list<static>
     */
    final public static function all(): array
    {
        $methods = [];
        foreach (static::essentialsAll() as $method) {
            if (! ($method instanceof TwoFactor)) {
                Logger::warning(
                    'Hook "%s" is registered for "%s" but does not implement %s. Ignoring it.',
                    $method::class,
                    static::getHookName(),
                    TwoFactor::class,
                );

                continue;
            }

            $methods[] = $method;
        }

        usort($methods, fn(TwoFactor $a, TwoFactor $b) => $a->getDisplayName() <=> $b->getDisplayName());

        return $methods;
    }

    /**
     * Get the implementation with the given canonical name
     *
     * @param string $canonicalName The globally unique identifier of the desired implementation,
     *   as returned by {@see getCanonicalName()}
     *
     * @return static The matching implementation
     *
     * @throws RuntimeException If no such implementation is registered
     */
    final public static function fromCanonicalName(string $canonicalName): static
    {
        foreach (static::all() as $method) {
            if ($method->getCanonicalName() === $canonicalName) {
                return $method;
            }
        }

        throw new RuntimeException(sprintf('No two-factor method found with name "%s"', $canonicalName));
    }

    /**
     * Get the implementation a user is enrolled in, or null if they are not enrolled
     *
     * Returns null when the user has no active enrollment in any currently registered
     * method. If the module providing the user's enrolled method is disabled, the
     * implementation is not registered and this method returns null and login succeeds
     * without a second factor. This is expected behavior when an administrator disables
     * a 2FA module.
     *
     * @param User $user The user to check for
     *
     * @return ?static The enrolled implementation, or null if the user is not enrolled in
     *   any registered method
     *
     * @throws Throwable If any registered implementation's {@see TwoFactor::isEnrolled()} throws
     */
    final public static function loadEnrolled(User $user): ?static
    {
        foreach (static::all() as $method) {
            try {
                $enrolled = $method->isEnrolled($user);
            } catch (Throwable $e) {
                Logger::error("%s\n%s", $e->getMessage(), IcingaException::getConfidentialTraceAsString($e));

                throw $e;
            }

            if ($enrolled) {
                return $method;
            }
        }

        return null;
    }

    /**
     * Yield display names indexed by the globally unique method identifier
     *
     * @return Generator<string, string>
     */
    final public static function yieldMethods(): Generator
    {
        foreach (static::all() as $method) {
            yield $method->getCanonicalName() => $method->getDisplayName();
        }
    }

    /**
     * Get the globally unique identifier for this 2FA method
     *
     * Combines the providing module's name with {@see getName()}, e.g. 'mymodule/totp',
     * so that two modules may register a method using the same {@see getName()} without
     * colliding. Falls back to the plain {@see getName()} for hook classes that are not
     * part of a module namespace.
     *
     * @return string
     */
    final public function getCanonicalName(): string
    {
        if ($module = $this->getModule()?->getName()) {
            return sprintf('%s/%s', $module, $this->getName());
        }

        return $this->getName();
    }
}
