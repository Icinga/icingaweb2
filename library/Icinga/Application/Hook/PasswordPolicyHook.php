<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Application\Hook;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Authentication\PasswordPolicy;
use Generator;
use RuntimeException;

/**
 * Base class for hookable password policies
 */
abstract class PasswordPolicyHook implements PasswordPolicy
{
    use HookEssentials {
        all as private essentialsAll;
    }

    /** @var string Default password policy class */
    public const DEFAULT_PASSWORD_POLICY = 'any';

    /** @var string INI configuration section for password policy */
    public const CONFIG_SECTION = 'security';

    /** @var string INI configuration key for password policy */
    public const CONFIG_KEY = 'password_policy';

    final protected static function getHookName(): string
    {
        return 'PasswordPolicy';
    }

    /**
     * Get whether the hook always runs without a permission check
     *
     * Password policies are a system hook and should always run for every user
     * regardless of the user's permission to access the module.
     */
    final protected static function isAlwaysRun(): bool
    {
        return true;
    }

    /**
     * Get all registered password policies
     *
     * Password policies are sorted by their display name.
     *
     * @return list<PasswordPolicyHook>
     */
    final public static function all(): array
    {
        $policies = [];
        foreach (self::essentialsAll() as $policy) {
            if (! ($policy instanceof PasswordPolicy)) {
                Logger::warning('Password policy %s is not an instance of PasswordPolicy', $policy::class);

                continue;
            }

            $policies[] = $policy;
        }

        usort($policies, fn(PasswordPolicy $a, PasswordPolicy $b) => $a->getDisplayName() <=> $b->getDisplayName());

        return $policies;
    }

    /**
     * Get a password policy instance by its canonical name
     *
     * @param string $canonicalName The canonical name of the password policy
     *
     * @return PasswordPolicyHook
     *
     * @throws RuntimeException If no such policy is registered
     */
    final public static function fromCanonicalName(string $canonicalName): self
    {
        foreach (self::all() as $policy) {
            if ($policy->getCanonicalName() === $canonicalName) {
                return $policy;
            }
        }

        throw new RuntimeException("No password policy found for canonical name '$canonicalName'");
    }

    /**
     * Get the currently configured password policy
     *
     * @param Config $config Configuration containing the password policy
     *
     * @return PasswordPolicyHook
     */
    final public static function loadConfigured(Config $config): self
    {
        return self::fromCanonicalName(
            $config->get(self::CONFIG_SECTION, self::CONFIG_KEY, self::DEFAULT_PASSWORD_POLICY)
        );
    }

    /**
     * Yield display names indexed by the canonical policy name
     *
     * @return Generator<string, string>
     */
    final public static function yieldPolicies(): Generator
    {
        foreach (self::all() as $policy) {
            yield $policy->getCanonicalName() => $policy->getDisplayName();
        }
    }

    /**
     * Get the globally unique identifier for this password policy
     *
     * Combines the providing module's name with {@see getDisplayName()}, e.g. 'mymodule/password-policy',
     * so that two modules may register a method using the same {@see getDisplayName()} without
     * colliding. Falls back to the plain {@see getDisplayName()} for hook classes that are not
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
